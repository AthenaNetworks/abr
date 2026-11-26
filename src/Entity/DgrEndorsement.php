<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

use AthenaNetworks\Abr\Period;

/**
 * Deductible Gift Recipient endorsement of the entity itself.
 */
final readonly class DgrEndorsement
{
    public function __construct(
        public Period $period,
        public ?string $endorsement,
        public ?string $itemNumber,
    ) {
    }
}
