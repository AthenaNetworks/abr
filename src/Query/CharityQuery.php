<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Query;

use AthenaNetworks\Abr\State;

/**
 * Find ABNs that hold charity tax concessions.
 *
 *     CharityQuery::create()->inPostcode('2600')->charityType('PBI');
 *
 * The ABR doesn't publish the valid charity type or concession codes, and returns no results
 * (rather than an error) for codes it doesn't recognise. With no filters at all, the search
 * returns every charity in Australia, which is tens of thousands of records.
 */
final class CharityQuery
{
    private ?string $postcode = null;
    private ?State $state = null;
    private ?string $charityTypeCode = null;
    private ?string $concessionTypeCode = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function inPostcode(string $postcode): self
    {
        $clone = clone $this;
        $clone->postcode = Postcode::normalise($postcode);

        return $clone;
    }

    public function inState(State $state): self
    {
        $clone = clone $this;
        $clone->state = $state;

        return $clone;
    }

    /** e.g. "PBI" (Public Benevolent Institution). */
    public function charityType(string $code): self
    {
        $clone = clone $this;
        $clone->charityTypeCode = trim($code);

        return $clone;
    }

    public function concessionType(string $code): self
    {
        $clone = clone $this;
        $clone->concessionTypeCode = trim($code);

        return $clone;
    }

    /** @return array<string, string> */
    public function toParameters(): array
    {
        return [
            'postcode' => $this->postcode ?? '',
            'state' => $this->state?->value ?? '',
            'charityTypeCode' => $this->charityTypeCode ?? '',
            'concessionTypeCode' => $this->concessionTypeCode ?? '',
        ];
    }
}
