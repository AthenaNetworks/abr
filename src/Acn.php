<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr;

use AthenaNetworks\Abr\Exceptions\InvalidIdentifierException;
use JsonSerializable;
use Stringable;

/**
 * An Australian Company Number (9 digits, ASIC mod-10 checksum).
 *
 * The ABR calls this an "ASIC number". It also covers ARBNs and ARSNs, which use the same format.
 */
final readonly class Acn implements Stringable, JsonSerializable
{
    private function __construct(public string $value)
    {
    }

    /**
     * @throws InvalidIdentifierException
     */
    public static function fromString(string $acn): self
    {
        return self::tryFromString($acn) ?? throw InvalidIdentifierException::acn($acn);
    }

    public static function tryFromString(string $acn): ?self
    {
        $digits = self::normalise($acn);

        return self::checksumIsValid($digits) ? new self($digits) : null;
    }

    public static function isValid(string $acn): bool
    {
        return self::checksumIsValid(self::normalise($acn));
    }

    /** "005 749 986" */
    public function formatted(): string
    {
        return implode(' ', str_split($this->value, 3));
    }

    public function equals(self|string $other): bool
    {
        $other = $other instanceof self ? $other->value : self::normalise($other);

        return $this->value === $other;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    private static function normalise(string $acn): string
    {
        return (string) preg_replace('/[\s\-]/', '', $acn);
    }

    private static function checksumIsValid(string $digits): bool
    {
        if (preg_match('/^\d{9}$/', $digits) !== 1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += (int) $digits[$i] * (8 - $i);
        }

        return (10 - $sum % 10) % 10 === (int) $digits[8];
    }
}
