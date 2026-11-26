<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Query;

/**
 * @internal
 */
final class Flag
{
    public static function yn(bool $value): string
    {
        return $value ? 'Y' : 'N';
    }
}
