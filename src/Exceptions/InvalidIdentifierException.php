<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Exceptions;

/**
 * The ABN or ACN is malformed (bad length, characters or checksum).
 *
 * Thrown locally before any network call where possible, or when the ABR rejects the input.
 */
final class InvalidIdentifierException extends AbrException
{
    public static function abn(string $value): self
    {
        return new self(sprintf('"%s" is not a valid ABN.', $value));
    }

    public static function acn(string $value): self
    {
        return new self(sprintf('"%s" is not a valid ACN.', $value));
    }
}
