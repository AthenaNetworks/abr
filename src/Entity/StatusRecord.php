<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

use AthenaNetworks\Abr\AbnStatus;
use AthenaNetworks\Abr\Period;

final readonly class StatusRecord
{
    public function __construct(
        public AbnStatus $status,
        public Period $period,
    ) {
    }
}
