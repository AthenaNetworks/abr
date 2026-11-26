<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Search;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Name search results, ordered as the ABR ranks them (best match first).
 *
 * @implements IteratorAggregate<int, SearchResult>
 */
final readonly class SearchResults implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param list<SearchResult> $results
     * @param bool $exceedsMaximum True when more records matched than were returned.
     */
    public function __construct(
        public array $results,
        public bool $exceedsMaximum = false,
    ) {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function first(): ?SearchResult
    {
        return $this->results[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->results === [];
    }

    /**
     * @param callable(SearchResult): bool $callback
     */
    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->results, $callback)), $this->exceedsMaximum);
    }

    /**
     * Keep only the best-scoring match for each ABN (an ABN can match on several names).
     */
    public function uniqueByAbn(): self
    {
        $seen = [];
        $unique = [];
        foreach ($this->results as $result) {
            if (! isset($seen[$result->abn->value])) {
                $seen[$result->abn->value] = true;
                $unique[] = $result;
            }
        }

        return new self($unique, $this->exceedsMaximum);
    }

    public function count(): int
    {
        return count($this->results);
    }

    /** @return Traversable<int, SearchResult> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->results);
    }

    /** @return list<SearchResult> */
    public function jsonSerialize(): array
    {
        return $this->results;
    }
}
