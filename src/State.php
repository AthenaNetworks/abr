<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr;

enum State: string
{
    case NSW = 'NSW';
    case VIC = 'VIC';
    case QLD = 'QLD';
    case SA = 'SA';
    case WA = 'WA';
    case TAS = 'TAS';
    case NT = 'NT';
    case ACT = 'ACT';

    public function label(): string
    {
        return match ($this) {
            self::NSW => 'New South Wales',
            self::VIC => 'Victoria',
            self::QLD => 'Queensland',
            self::SA => 'South Australia',
            self::WA => 'Western Australia',
            self::TAS => 'Tasmania',
            self::NT => 'Northern Territory',
            self::ACT => 'Australian Capital Territory',
        };
    }
}
