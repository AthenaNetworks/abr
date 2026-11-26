<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Search;

use ArrayIterator;
use AthenaNetworks\Abr\Abn;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * A plain list of ABNs, returned by the postcode, charity and status searches.
 *
 * @implements IteratorAggregate<int, Abn>
 */
final readonly class AbnList implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param list<Abn> $abns
     */
    public function __construct(public array $abns)
    {
    }

    public function contains(Abn|string $abn): bool
    {
        foreach ($this->abns as $candidate) {
            if ($candidate->equals($abn)) {
                return true;
            }
        }

        return false;
    }

    public function isEmpty(): bool
    {
        return $this->abns === [];
    }

    /** @return list<string> */
    public function toStrings(): array
    {
        return array_map(static fn (Abn $abn): string => $abn->value, $this->abns);
    }

    public function count(): int
    {
        return count($this->abns);
    }

    /** @return Traversable<int, Abn> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->abns);
    }

    /** @return list<string> */
    public function jsonSerialize(): array
    {
        return $this->toStrings();
    }
}
