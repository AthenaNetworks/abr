<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

use AthenaNetworks\Abr\Period;

/**
 * A Deductible Gift Recipient fund, authority or institution operated by the entity.
 */
final readonly class DgrFund
{
    /**
     * @param list<string> $names
     */
    public function __construct(
        public array $names,
        public Period $period,
        public ?string $itemNumber,
    ) {
    }

    public function name(): ?string
    {
        return $this->names[0] ?? null;
    }
}
