<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

use AthenaNetworks\Abr\Period;

/**
 * A charity tax concession endorsement, e.g. "GST Concession" or "Income Tax Exemption".
 */
final readonly class TaxConcession
{
    public function __construct(
        public string $type,
        public Period $period,
    ) {
    }
}
