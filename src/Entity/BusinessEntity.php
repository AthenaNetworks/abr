<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

use AthenaNetworks\Abr\Abn;
use AthenaNetworks\Abr\AbnStatus;
use AthenaNetworks\Abr\Acn;
use AthenaNetworks\Abr\Period;
use DateTimeImmutable;
use JsonSerializable;

/**
 * A full ABR record for one ABN.
 *
 * Every list property is ordered as the ABR returns it, current record first. Lists contain only
 * current records unless the lookup asked for historical details.
 */
final readonly class BusinessEntity implements JsonSerializable
{
    /**
     * @param list<StatusRecord>       $statusHistory
     * @param list<Period>             $gstRegistrations
     * @param list<IndividualName>     $legalNames        Individuals (sole traders) only.
     * @param list<OrganisationName>   $mainNames         Non-individuals only.
     * @param list<OrganisationName>   $mainTradingNames
     * @param list<OrganisationName>   $otherTradingNames
     * @param list<OrganisationName>   $businessNames     Registered business names.
     * @param list<PhysicalAddress>    $addresses
     * @param list<DgrEndorsement>     $dgrEndorsements
     * @param list<DgrFund>            $dgrFunds
     * @param list<CharityType>        $charityTypes
     * @param list<TaxConcession>      $taxConcessions
     * @param list<AcncRegistration>   $acncRegistrations
     */
    public function __construct(
        public Abn $abn,
        public ?Acn $acn,
        public EntityType $entityType,
        public ?DateTimeImmutable $recordLastUpdated,
        public array $statusHistory,
        public array $gstRegistrations,
        public array $legalNames,
        public array $mainNames,
        public array $mainTradingNames,
        public array $otherTradingNames,
        public array $businessNames,
        public array $addresses,
        public array $dgrEndorsements = [],
        public array $dgrFunds = [],
        public array $charityTypes = [],
        public array $taxConcessions = [],
        public array $acncRegistrations = [],
        public ?SuperannuationStatus $superannuationStatus = null,
    ) {
    }

    /**
     * The entity's current legal name: the individual's full name, or the organisation's main name.
     */
    public function name(): string
    {
        $legal = self::current($this->legalNames);
        if ($legal !== null) {
            return $legal->fullName();
        }

        return self::current($this->mainNames)?->name ?? '';
    }

    public function status(): AbnStatus
    {
        return (self::current($this->statusHistory) ?? $this->statusHistory[0] ?? null)?->status
            ?? AbnStatus::Cancelled;
    }

    public function isActive(): bool
    {
        return $this->status() === AbnStatus::Active;
    }

    public function isIndividual(): bool
    {
        return $this->entityType->isIndividual();
    }

    public function isGstRegistered(): bool
    {
        return self::current($this->gstRegistrations) !== null;
    }

    /** The start date of the current GST registration, if any. */
    public function gstRegisteredFrom(): ?DateTimeImmutable
    {
        return self::current($this->gstRegistrations)?->from;
    }

    public function address(): ?PhysicalAddress
    {
        return self::current($this->addresses);
    }

    /** @return list<string> */
    public function tradingNames(): array
    {
        return self::currentNames([...$this->mainTradingNames, ...$this->otherTradingNames]);
    }

    /** @return list<string> */
    public function currentBusinessNames(): array
    {
        return self::currentNames($this->businessNames);
    }

    public function isDgrEndorsed(): bool
    {
        return self::current($this->dgrEndorsements) !== null;
    }

    public function isCharity(): bool
    {
        return self::current($this->charityTypes) !== null
            || self::current($this->taxConcessions) !== null
            || $this->isAcncRegistered();
    }

    public function isAcncRegistered(): bool
    {
        foreach ($this->acncRegistrations as $registration) {
            if ($registration->isRegistered()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $address = $this->address();

        return [
            'abn' => $this->abn->value,
            'acn' => $this->acn?->value,
            'name' => $this->name(),
            'status' => $this->status()->value,
            'entity_type' => $this->entityType,
            'gst_registered' => $this->isGstRegistered(),
            'gst_registered_from' => $this->gstRegisteredFrom()?->format('Y-m-d'),
            'state' => $address?->state?->value,
            'postcode' => $address?->postcode,
            'trading_names' => $this->tradingNames(),
            'business_names' => $this->currentBusinessNames(),
            'dgr_endorsed' => $this->isDgrEndorsed(),
            'charity' => $this->isCharity(),
            'record_last_updated' => $this->recordLastUpdated?->format('Y-m-d'),
        ];
    }

    /**
     * The first record whose period is still open (the ABR lists current records first).
     *
     * @template T of object
     *
     * @param list<T> $records Period instances, or objects with a public Period $period.
     *
     * @return T|null
     */
    private static function current(array $records): ?object
    {
        foreach ($records as $record) {
            $period = $record instanceof Period ? $record : $record->period;
            if ($period->isCurrent()) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @param list<OrganisationName> $names
     *
     * @return list<string>
     */
    private static function currentNames(array $names): array
    {
        $current = [];
        foreach ($names as $name) {
            if ($name->period->isCurrent()) {
                $current[] = $name->name;
            }
        }

        return array_values(array_unique($current));
    }
}
