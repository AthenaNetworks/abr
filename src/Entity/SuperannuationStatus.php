<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

/**
 * Superannuation fund compliance status (super funds only).
 */
final readonly class SuperannuationStatus
{
    public function __construct(
        public ?string $complyingCode,
        public ?string $complyingDescription,
        public ?string $regulator,
    ) {
    }
}
