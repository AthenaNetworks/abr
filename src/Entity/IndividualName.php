<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

use AthenaNetworks\Abr\Period;
use Stringable;

/**
 * The legal name of an individual (sole trader).
 */
final readonly class IndividualName implements Stringable
{
    public function __construct(
        public ?string $givenName,
        public ?string $otherGivenName,
        public ?string $familyName,
        public Period $period,
    ) {
    }

    public function fullName(): string
    {
        return implode(' ', array_filter(
            [$this->givenName, $this->otherGivenName, $this->familyName],
            static fn (?string $part): bool => $part !== null && $part !== '',
        ));
    }

    public function __toString(): string
    {
        return $this->fullName();
    }
}
