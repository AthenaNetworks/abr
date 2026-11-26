<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr;

enum AbnStatus: string
{
    case Active = 'Active';
    case Cancelled = 'Cancelled';
}
