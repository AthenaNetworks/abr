<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr;

use AthenaNetworks\Abr\Exceptions\InvalidIdentifierException;
use JsonSerializable;
use Stringable;

/**
 * An Australian Business Number (11 digits, mod-89 checksum).
 *
 * Construction guarantees validity, so an Abn instance is always well formed.
 */
final readonly class Abn implements Stringable, JsonSerializable
{
    private const array WEIGHTS = [10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19];

    private function __construct(public string $value)
    {
    }

    /**
     * @throws InvalidIdentifierException
     */
    public static function fromString(string $abn): self
    {
        return self::tryFromString($abn) ?? throw InvalidIdentifierException::abn($abn);
    }

    public static function tryFromString(string $abn): ?self
    {
        $digits = self::normalise($abn);

        return self::checksumIsValid($digits) ? new self($digits) : null;
    }

    public static function isValid(string $abn): bool
    {
        return self::checksumIsValid(self::normalise($abn));
    }

    /** "51 824 753 556" */
    public function formatted(): string
    {
        return sprintf(
            '%s %s %s %s',
            substr($this->value, 0, 2),
            substr($this->value, 2, 3),
            substr($this->value, 5, 3),
            substr($this->value, 8, 3),
        );
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

    private static function normalise(string $abn): string
    {
        return (string) preg_replace('/[\s\-]/', '', $abn);
    }

    private static function checksumIsValid(string $digits): bool
    {
        if (preg_match('/^[1-9]\d{10}$/', $digits) !== 1) {
            return false;
        }

        $sum = 0;
        foreach (str_split($digits) as $i => $digit) {
            $sum += ((int) $digit - ($i === 0 ? 1 : 0)) * self::WEIGHTS[$i];
        }

        return $sum % 89 === 0;
    }
}
