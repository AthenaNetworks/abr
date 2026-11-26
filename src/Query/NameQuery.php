<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Query;

use AthenaNetworks\Abr\Exceptions\InvalidQueryException;
use AthenaNetworks\Abr\SearchWidth;
use AthenaNetworks\Abr\State;

/**
 * An immutable, fluent name search.
 *
 *     NameQuery::for('Athena Networks')
 *         ->inStates(State::NSW, State::VIC)
 *         ->activeOnly()
 *         ->limit(20);
 *
 * Every "with" method returns a new instance.
 */
final class NameQuery
{
    private ?string $postcode = null;

    /** @var array<string, State> */
    private array $states = [];

    private bool $legalNames = false;
    private bool $tradingNames = false;
    private bool $businessNames = false;
    private bool $activeOnly = false;
    private ?SearchWidth $width = null;
    private ?int $minimumScore = null;
    private ?int $limit = null;

    private function __construct(public readonly string $name)
    {
    }

    /**
     * @throws InvalidQueryException
     */
    public static function for(string $name): self
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidQueryException('A name search needs a non-empty name.');
        }

        return new self($name);
    }

    public function inPostcode(string $postcode): self
    {
        $clone = clone $this;
        $clone->postcode = Postcode::normalise($postcode);

        return $clone;
    }

    /**
     * Limit results to these states. All states are searched if this is never called.
     */
    public function inStates(State ...$states): self
    {
        $clone = clone $this;
        foreach ($states as $state) {
            $clone->states[$state->value] = $state;
        }

        return $clone;
    }

    /**
     * Search only these name types. All three are searched if none is chosen.
     */
    public function legalNames(): self
    {
        $clone = clone $this;
        $clone->legalNames = true;

        return $clone;
    }

    public function tradingNames(): self
    {
        $clone = clone $this;
        $clone->tradingNames = true;

        return $clone;
    }

    public function businessNames(): self
    {
        $clone = clone $this;
        $clone->businessNames = true;

        return $clone;
    }

    /** Exclude cancelled ABNs. */
    public function activeOnly(bool $activeOnly = true): self
    {
        $clone = clone $this;
        $clone->activeOnly = $activeOnly;

        return $clone;
    }

    public function width(SearchWidth $width): self
    {
        $clone = clone $this;
        $clone->width = $width;

        return $clone;
    }

    /**
     * @param int<0, 100> $score
     */
    public function minimumScore(int $score): self
    {
        if ($score < 0 || $score > 100) {
            throw new InvalidQueryException('The minimum score must be between 0 and 100.');
        }

        $clone = clone $this;
        $clone->minimumScore = $score;

        return $clone;
    }

    /**
     * The maximum number of results. The ABR defaults to 200.
     *
     * @param positive-int $limit
     */
    public function limit(int $limit): self
    {
        if ($limit < 1) {
            throw new InvalidQueryException('The result limit must be at least 1.');
        }

        $clone = clone $this;
        $clone->limit = $limit;

        return $clone;
    }

    /**
     * The HTTP parameters for ABRSearchByNameAdvancedSimpleProtocol2017, excluding the GUID.
     *
     * Every parameter is always sent, because the ABR rejects requests that leave any out.
     *
     * @return array<string, string>
     */
    public function toParameters(): array
    {
        $anyNameType = $this->legalNames || $this->tradingNames || $this->businessNames;

        $parameters = [
            'name' => $this->name,
            'postcode' => $this->postcode ?? '',
            'legalName' => Flag::yn(! $anyNameType || $this->legalNames),
            'tradingName' => Flag::yn(! $anyNameType || $this->tradingNames),
            'businessName' => Flag::yn(! $anyNameType || $this->businessNames),
            'activeABNsOnly' => Flag::yn($this->activeOnly),
        ];

        foreach (State::cases() as $state) {
            $parameters[$state->value] = Flag::yn($this->states === [] || isset($this->states[$state->value]));
        }

        $parameters['searchWidth'] = $this->width?->value ?? '';
        $parameters['minimumScore'] = $this->minimumScore === null ? '' : (string) $this->minimumScore;
        $parameters['maxSearchResults'] = $this->limit === null ? '' : (string) $this->limit;

        return $parameters;
    }
}
