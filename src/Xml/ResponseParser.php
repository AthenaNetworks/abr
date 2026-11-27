<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Xml;

use AthenaNetworks\Abr\Abn;
use AthenaNetworks\Abr\AbnStatus;
use AthenaNetworks\Abr\Acn;
use AthenaNetworks\Abr\Entity\AcncRegistration;
use AthenaNetworks\Abr\Entity\BusinessEntity;
use AthenaNetworks\Abr\Entity\CharityType;
use AthenaNetworks\Abr\Entity\DgrEndorsement;
use AthenaNetworks\Abr\Entity\DgrFund;
use AthenaNetworks\Abr\Entity\EntityType;
use AthenaNetworks\Abr\Entity\IndividualName;
use AthenaNetworks\Abr\Entity\OrganisationName;
use AthenaNetworks\Abr\Entity\PhysicalAddress;
use AthenaNetworks\Abr\Entity\StatusRecord;
use AthenaNetworks\Abr\Entity\SuperannuationStatus;
use AthenaNetworks\Abr\Entity\TaxConcession;
use AthenaNetworks\Abr\Exceptions\AbrException;
use AthenaNetworks\Abr\Exceptions\AuthenticationException;
use AthenaNetworks\Abr\Exceptions\InvalidIdentifierException;
use AthenaNetworks\Abr\Exceptions\NotFoundException;
use AthenaNetworks\Abr\Exceptions\ServiceException;
use AthenaNetworks\Abr\Exceptions\UnexpectedResponseException;
use AthenaNetworks\Abr\NameType;
use AthenaNetworks\Abr\Period;
use AthenaNetworks\Abr\Search\AbnList;
use AthenaNetworks\Abr\Search\SearchResult;
use AthenaNetworks\Abr\Search\SearchResults;
use AthenaNetworks\Abr\State;
use DateTimeImmutable;
use DateTimeZone;
use SimpleXMLElement;

/**
 * Turns ABRPayloadSearchResults XML documents into domain objects.
 *
 * Quirks handled here:
 *  - business errors arrive as HTTP 200 with a <response><exception> element;
 *  - "no date" is the sentinel 0001-01-01, or the element is left out;
 *  - booleans are the strings "Y" and "N".
 *
 * @internal
 */
final class ResponseParser
{
    public const string NAMESPACE = 'http://abr.business.gov.au/ABRXMLSearch/';

    private const string NULL_DATE = '0001-01-01';

    private DateTimeZone $timezone;

    public function __construct(?DateTimeZone $timezone = null)
    {
        $this->timezone = $timezone ?? new DateTimeZone('Australia/Sydney');
    }

    public function businessEntity(string $xml): BusinessEntity
    {
        $response = $this->response($xml);

        foreach ($response->children(self::NAMESPACE) as $name => $node) {
            if (str_starts_with($name, 'businessEntity')) {
                return $this->parseEntity($node);
            }
        }

        throw new UnexpectedResponseException('The ABR response contained no business entity.');
    }

    public function searchResults(string $xml): SearchResults
    {
        try {
            $response = $this->response($xml);
        } catch (NotFoundException) {
            return SearchResults::empty();
        }

        $list = $this->child($response, 'searchResultsList')
            ?? throw new UnexpectedResponseException('The ABR response contained no search results list.');

        $results = [];
        foreach ($list->children(self::NAMESPACE)->searchResultsRecord as $record) {
            $result = $this->parseSearchRecord($record);
            if ($result !== null) {
                $results[] = $result;
            }
        }

        return new SearchResults($results, $this->flag($list, 'exceedsMaximum'));
    }

    public function abnList(string $xml): AbnList
    {
        try {
            $response = $this->response($xml);
        } catch (NotFoundException) {
            return new AbnList([]);
        }

        $list = $this->child($response, 'abnList')
            ?? throw new UnexpectedResponseException('The ABR response contained no ABN list.');

        $abns = [];
        foreach ($list->children(self::NAMESPACE)->abn as $abn) {
            $value = Abn::tryFromString(trim((string) $abn));
            if ($value !== null) {
                $abns[] = $value;
            }
        }

        return new AbnList($abns);
    }

    /**
     * Load the document and return the <response> element, throwing for ABR-reported errors.
     */
    private function response(string $xml): SimpleXMLElement
    {
        $xml = ltrim($xml, "\xEF\xBB\xBF \t\r\n");

        $previous = libxml_use_internal_errors(true);
        try {
            $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($document === false) {
            throw new UnexpectedResponseException(
                'The ABR returned a response that is not valid XML: ' . self::excerpt($xml),
            );
        }

        $response = $this->child($document, 'response')
            ?? throw new UnexpectedResponseException('The ABR response has no <response> element.');

        $exception = $this->child($response, 'exception');
        if ($exception !== null) {
            throw self::exceptionFor(
                $this->text($exception, 'exceptionDescription') ?? 'Unknown ABR error',
                $this->text($exception, 'exceptionCode'),
            );
        }

        return $response;
    }

    /**
     * The ABR's exception codes are too coarse to act on (see docs/abr-api-shortcomings.md §3),
     * so the description text has to be matched as well.
     */
    private static function exceptionFor(string $description, ?string $code): AbrException
    {
        $lower = strtolower($description);

        return match (true) {
            str_contains($lower, 'no records found') => new NotFoundException($description),
            str_contains($lower, 'guid') => new AuthenticationException($description),
            str_contains($lower, 'not a valid abn'),
            str_contains($lower, 'not a valid acn'),
            str_contains($lower, 'is not a valid') => new InvalidIdentifierException($description),
            default => new ServiceException($description, $code),
        };
    }

    private function parseEntity(SimpleXMLElement $node): BusinessEntity
    {
        $c = $node->children(self::NAMESPACE);

        $abn = null;
        foreach ($c->ABN as $identifier) {
            if ($this->flag($identifier, 'isCurrentIndicator') || $abn === null) {
                $abn = Abn::tryFromString((string) $this->text($identifier, 'identifierValue')) ?? $abn;
            }
        }
        if ($abn === null) {
            throw new UnexpectedResponseException('The business entity has no valid ABN.');
        }

        $acnText = $this->text($node, 'ASICNumber');
        $entityTypeNode = $this->child($node, 'entityType');

        return new BusinessEntity(
            abn: $abn,
            acn: $acnText === null ? null : Acn::tryFromString($acnText),
            entityType: new EntityType(
                $entityTypeNode === null ? '' : ($this->text($entityTypeNode, 'entityTypeCode') ?? ''),
                $entityTypeNode === null ? '' : ($this->text($entityTypeNode, 'entityDescription') ?? ''),
            ),
            recordLastUpdated: $this->date($this->text($node, 'recordLastUpdatedDate')),
            statusHistory: $this->map($c->entityStatus, fn (SimpleXMLElement $s): ?StatusRecord =>
                ($status = AbnStatus::tryFrom((string) $this->text($s, 'entityStatusCode'))) === null
                    ? null
                    : new StatusRecord($status, $this->period($s))),
            gstRegistrations: $this->map($c->goodsAndServicesTax, fn (SimpleXMLElement $g): Period => $this->period($g)),
            legalNames: $this->map($c->legalName, fn (SimpleXMLElement $n): IndividualName => new IndividualName(
                $this->text($n, 'givenName'),
                $this->text($n, 'otherGivenName'),
                $this->text($n, 'familyName'),
                $this->period($n),
            )),
            mainNames: $this->organisationNames($c->mainName),
            mainTradingNames: $this->organisationNames($c->mainTradingName),
            otherTradingNames: $this->organisationNames($c->otherTradingName),
            businessNames: $this->organisationNames($c->businessName),
            addresses: $this->map($c->mainBusinessPhysicalAddress, fn (SimpleXMLElement $a): PhysicalAddress => new PhysicalAddress(
                State::tryFrom(strtoupper((string) $this->text($a, 'stateCode'))),
                $this->text($a, 'postcode'),
                $this->period($a),
            )),
            dgrEndorsements: $this->map($c->dgrEndorsement, fn (SimpleXMLElement $d): DgrEndorsement => new DgrEndorsement(
                $this->period($d, 'endorsedFrom', 'endorsedTo'),
                $this->text($d, 'entityEndorsement'),
                $this->text($d, 'itemNumber'),
            )),
            dgrFunds: $this->map($c->dgrFund, fn (SimpleXMLElement $f): DgrFund => new DgrFund(
                $this->map($f->children(self::NAMESPACE)->dgrFundName, fn (SimpleXMLElement $n): ?string => $this->text($n, 'organisationName')),
                $this->period($f, 'endorsedFrom', 'endorsedTo'),
                $this->text($f, 'itemNumber'),
            )),
            charityTypes: $this->map($c->charityType, fn (SimpleXMLElement $t): ?CharityType =>
                ($description = $this->text($t, 'charityTypeDescription')) === null
                    ? null
                    : new CharityType($description, $this->period($t))),
            taxConcessions: $this->map($c->taxConcessionCharityEndorsement, fn (SimpleXMLElement $t): ?TaxConcession =>
                ($type = $this->text($t, 'endorsementType')) === null
                    ? null
                    : new TaxConcession($type, $this->period($t))),
            acncRegistrations: $this->map($c->ACNCRegistration, fn (SimpleXMLElement $r): AcncRegistration => new AcncRegistration(
                $this->text($r, 'status') ?? '',
                $this->period($r),
            )),
            superannuationStatus: ($super = $this->child($node, 'superannuationStatus')) === null ? null : new SuperannuationStatus(
                $this->text($super, 'complyingCode'),
                $this->text($super, 'complyingDescription'),
                $this->text($super, 'regulator'),
            ),
        );
    }

    private function parseSearchRecord(SimpleXMLElement $record): ?SearchResult
    {
        $c = $record->children(self::NAMESPACE);

        $identifier = $this->child($record, 'ABN');
        $abn = $identifier === null ? null : Abn::tryFromString((string) $this->text($identifier, 'identifierValue'));
        if ($abn === null) {
            return null;
        }

        $nameNode = null;
        $nameType = null;
        foreach ($c as $element => $child) {
            $nameType = NameType::tryFrom($element);
            if ($nameType !== null) {
                $nameNode = $child;
                break;
            }
        }
        if ($nameNode === null || $nameType === null) {
            return null;
        }

        $name = $nameType === NameType::LegalName
            ? ($this->text($nameNode, 'fullName') ?? trim(implode(' ', array_filter([
                $this->text($nameNode, 'givenName'),
                $this->text($nameNode, 'otherGivenName'),
                $this->text($nameNode, 'familyName'),
            ]))))
            : (string) $this->text($nameNode, 'organisationName');

        // The schema allows several addresses per record, each flagged with isCurrentIndicator.
        $address = null;
        foreach ($c->mainBusinessPhysicalAddress as $candidate) {
            if ($this->flag($candidate, 'isCurrentIndicator')) {
                $address = $candidate;
                break;
            }
            $address ??= $candidate;
        }

        return new SearchResult(
            abn: $abn,
            abnStatus: AbnStatus::tryFrom((string) $this->text($identifier, 'identifierStatus')) ?? AbnStatus::Cancelled,
            name: $name,
            nameType: $nameType,
            score: (int) $this->text($nameNode, 'score'),
            isCurrentName: $this->flag($nameNode, 'isCurrentIndicator'),
            state: $address === null ? null : State::tryFrom(strtoupper((string) $this->text($address, 'stateCode'))),
            postcode: $address === null ? null : $this->text($address, 'postcode'),
        );
    }

    /**
     * @return list<OrganisationName>
     */
    private function organisationNames(SimpleXMLElement $nodes): array
    {
        return $this->map($nodes, fn (SimpleXMLElement $n): ?OrganisationName =>
            ($name = $this->text($n, 'organisationName')) === null
                ? null
                : new OrganisationName($name, $this->period($n)));
    }

    /**
     * Map a SimpleXML node list, dropping nulls.
     *
     * @template T
     *
     * @param callable(SimpleXMLElement): (T|null) $callback
     *
     * @return list<T>
     */
    private function map(SimpleXMLElement $nodes, callable $callback): array
    {
        $mapped = [];
        foreach ($nodes as $node) {
            $value = $callback($node);
            if ($value !== null) {
                $mapped[] = $value;
            }
        }

        return $mapped;
    }

    private function period(SimpleXMLElement $node, string $from = 'effectiveFrom', string $to = 'effectiveTo'): Period
    {
        return new Period(
            $this->date($this->text($node, $from)),
            $this->date($this->text($node, $to)),
        );
    }

    private function child(SimpleXMLElement $node, string $name): ?SimpleXMLElement
    {
        $child = $node->children(self::NAMESPACE)->{$name};

        return $child->count() > 0 ? $child[0] : null;
    }

    private function text(SimpleXMLElement $node, string $name): ?string
    {
        $child = $this->child($node, $name);
        if ($child === null) {
            return null;
        }

        $text = trim((string) $child);

        return $text === '' ? null : $text;
    }

    private function flag(SimpleXMLElement $node, string $name): bool
    {
        return strtoupper((string) $this->text($node, $name)) === 'Y';
    }

    private function date(?string $value): ?DateTimeImmutable
    {
        if ($value === null || str_starts_with($value, self::NULL_DATE)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10), $this->timezone);

        return $date === false ? null : $date;
    }

    private static function excerpt(string $body): string
    {
        $body = trim(preg_replace('/\s+/', ' ', $body) ?? '');

        return strlen($body) > 200 ? substr($body, 0, 200) . '...' : $body;
    }
}
