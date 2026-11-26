<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Query;

/**
 * Find ABNs in a postcode, filtered by ABN status, GST registration and entity type.
 *
 *     AbnStatusQuery::inPostcode('2600')->activeOnly()->gstRegisteredOnly()->entityType('PRV');
 *
 * A postcode is required, because the ABR treats an empty postcode as a real value.
 */
final class AbnStatusQuery
{
    private bool $activeOnly = false;
    private bool $gstRegisteredOnly = false;
    private ?string $entityTypeCode = null;

    private function __construct(public readonly string $postcode)
    {
    }

    public static function inPostcode(string $postcode): self
    {
        return new self(Postcode::normalise($postcode));
    }

    public function activeOnly(bool $activeOnly = true): self
    {
        $clone = clone $this;
        $clone->activeOnly = $activeOnly;

        return $clone;
    }

    public function gstRegisteredOnly(bool $gstRegisteredOnly = true): self
    {
        $clone = clone $this;
        $clone->gstRegisteredOnly = $gstRegisteredOnly;

        return $clone;
    }

    /** An ABR entity type code, e.g. "PRV" (private company) or "IND" (individual). */
    public function entityType(string $code): self
    {
        $clone = clone $this;
        $clone->entityTypeCode = strtoupper(trim($code));

        return $clone;
    }

    /** @return array<string, string> */
    public function toParameters(): array
    {
        return [
            'postcode' => $this->postcode,
            'activeABNsOnly' => Flag::yn($this->activeOnly),
            'currentGSTRegistrationOnly' => Flag::yn($this->gstRegisteredOnly),
            'entityTypeCode' => $this->entityTypeCode ?? '',
        ];
    }
}
