<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

use AthenaNetworks\Abr\Period;

final readonly class CharityType
{
    public function __construct(
        public string $description,
        public Period $period,
    ) {
    }
}
