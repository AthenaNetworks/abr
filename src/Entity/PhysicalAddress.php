<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

use AthenaNetworks\Abr\Period;
use AthenaNetworks\Abr\State;

/**
 * The main business location. The ABR only publishes state and postcode.
 */
final readonly class PhysicalAddress
{
    public function __construct(
        public ?State $state,
        public ?string $postcode,
        public Period $period,
    ) {
    }
}
