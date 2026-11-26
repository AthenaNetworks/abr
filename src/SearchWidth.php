<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr;

/**
 * How fuzzy a name search is.
 */
enum SearchWidth: string
{
    /** Closer matches only. */
    case Narrow = 'narrow';
    /** The ABR default. */
    case Typical = 'typical';
}
