<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

use AthenaNetworks\Abr\Period;
use Stringable;

/**
 * An organisation name: main (legal) name, trading name or registered business name.
 */
final readonly class OrganisationName implements Stringable
{
    public function __construct(
        public string $name,
        public Period $period,
    ) {
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
