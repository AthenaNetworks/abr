<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr;

use AthenaNetworks\Abr\Contracts\AbrClient as AbrClientContract;
use AthenaNetworks\Abr\Entity\BusinessEntity;
use AthenaNetworks\Abr\Http\Transport;
use AthenaNetworks\Abr\Query\AbnStatusQuery;
use AthenaNetworks\Abr\Query\CharityQuery;
use AthenaNetworks\Abr\Query\NameQuery;
use AthenaNetworks\Abr\Query\Postcode;
use AthenaNetworks\Abr\Search\AbnList;
use AthenaNetworks\Abr\Search\SearchResults;
use AthenaNetworks\Abr\Xml\ResponseParser;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Client for the ABR ABN Lookup web services.
 *
 *     $abr = new AbrClient('your-authentication-guid');
 *     $entity = $abr->lookupAbn('51 824 753 556');
 *
 * Any PSR-18 HTTP client works. If none is given, an installed one (Guzzle, Symfony HttpClient, ...)
 * is discovered automatically.
 */
final readonly class AbrClient implements AbrClientContract
{
    public const string DEFAULT_ENDPOINT = 'https://abr.business.gov.au/abrxmlsearch/abrxmlsearch.asmx';

    /*
     * Operation versions. ABRSearchByNameAdvancedSimpleProtocol2023 exists in the WSDL but the ABR
     * currently rejects it ("This feature is currently not available"), so 2017 is used.
     */
    private const string OP_ABN = 'SearchByABNv202001';
    private const string OP_ACN = 'SearchByASICv201408';
    private const string OP_NAME = 'ABRSearchByNameAdvancedSimpleProtocol2017';
    private const string OP_POSTCODE = 'SearchByPostcode';
    private const string OP_STATUS = 'SearchByABNStatus';
    private const string OP_CHARITY = 'SearchByCharity';

    private Transport $transport;
    private ResponseParser $parser;

    public function __construct(
        #[\SensitiveParameter]
        string $guid,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        string $endpoint = self::DEFAULT_ENDPOINT,
    ) {
        $this->transport = new Transport(
            $httpClient ?? Psr18ClientDiscovery::find(),
            $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory(),
            $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory(),
            $endpoint,
            $guid,
        );
        $this->parser = new ResponseParser();
    }

    public function lookupAbn(Abn|string $abn, bool $withHistory = false): BusinessEntity
    {
        $abn = $abn instanceof Abn ? $abn : Abn::fromString($abn);

        return $this->parser->businessEntity($this->transport->call(self::OP_ABN, [
            'searchString' => $abn->value,
            'includeHistoricalDetails' => $withHistory ? 'Y' : 'N',
        ]));
    }

    public function lookupAcn(Acn|string $acn, bool $withHistory = false): BusinessEntity
    {
        $acn = $acn instanceof Acn ? $acn : Acn::fromString($acn);

        return $this->parser->businessEntity($this->transport->call(self::OP_ACN, [
            'searchString' => $acn->value,
            'includeHistoricalDetails' => $withHistory ? 'Y' : 'N',
        ]));
    }

    public function searchByName(NameQuery|string $query): SearchResults
    {
        $query = $query instanceof NameQuery ? $query : NameQuery::for($query);

        return $this->parser->searchResults($this->transport->call(self::OP_NAME, $query->toParameters()));
    }

    public function searchByPostcode(string $postcode): AbnList
    {
        return $this->parser->abnList($this->transport->call(self::OP_POSTCODE, [
            'postcode' => Postcode::normalise($postcode),
        ]));
    }

    public function searchByStatus(AbnStatusQuery $query): AbnList
    {
        return $this->parser->abnList($this->transport->call(self::OP_STATUS, $query->toParameters()));
    }

    public function searchCharities(CharityQuery $query): AbnList
    {
        return $this->parser->abnList($this->transport->call(self::OP_CHARITY, $query->toParameters()));
    }
}
