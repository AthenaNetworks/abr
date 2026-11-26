<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr;

/**
 * Which kind of name a search result matched on.
 */
enum NameType: string
{
    /** An individual's (sole trader's) legal name. */
    case LegalName = 'legalName';
    /** An organisation's registered (main) name. */
    case MainName = 'mainName';
    case MainTradingName = 'mainTradingName';
    case OtherTradingName = 'otherTradingName';
    /** A registered business name (ASIC business names register). */
    case BusinessName = 'businessName';
    case DgrFundName = 'dgrFundName';
    /** Public Benevolent Institution Employer name. */
    case PbieName = 'PBIEName';
    /** Approved Worker Entitlement Fund name. */
    case AwefName = 'AWEFName';

    public function isTradingName(): bool
    {
        return $this === self::MainTradingName || $this === self::OtherTradingName;
    }
}
