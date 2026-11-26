<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Query;

use AthenaNetworks\Abr\Exceptions\InvalidQueryException;

/**
 * @internal
 */
final class Postcode
{
    /**
     * The ABR silently returns no results for malformed postcodes, so they are checked here.
     *
     * @throws InvalidQueryException
     */
    public static function normalise(string $postcode): string
    {
        $postcode = trim($postcode);
        if (preg_match('/^\d{4}$/', $postcode) !== 1) {
            throw InvalidQueryException::postcode($postcode);
        }

        return $postcode;
    }
}
