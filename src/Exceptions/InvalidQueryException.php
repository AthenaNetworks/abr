<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Exceptions;

/**
 * A search query is invalid (empty name, bad postcode, out-of-range score, ...).
 *
 * Always thrown locally, before any request is sent.
 */
final class InvalidQueryException extends AbrException
{
    public static function postcode(string $value): self
    {
        return new self(sprintf('"%s" is not a valid Australian postcode (expected 4 digits).', $value));
    }
}
