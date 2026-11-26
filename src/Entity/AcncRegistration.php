<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

use AthenaNetworks\Abr\Period;

/**
 * Registration with the Australian Charities and Not-for-profits Commission.
 */
final readonly class AcncRegistration
{
    public function __construct(
        public string $status,
        public Period $period,
    ) {
    }

    public function isRegistered(): bool
    {
        return $this->period->isCurrent() && strcasecmp($this->status, 'Registered') === 0;
    }
}
