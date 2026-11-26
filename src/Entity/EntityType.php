<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Entity;

use JsonSerializable;

/**
 * The ABR's legal entity type, e.g. "PRV" / "Australian Private Company".
 */
final readonly class EntityType implements JsonSerializable
{
    public function __construct(
        public string $code,
        public string $description,
    ) {
    }

    public function isIndividual(): bool
    {
        return $this->code === 'IND';
    }

    /** A company registered with ASIC (private or public). */
    public function isCompany(): bool
    {
        return in_array($this->code, ['PRV', 'PUB'], true);
    }

    public function isTrust(): bool
    {
        return str_contains(strtolower($this->description), 'trust');
    }

    /** @return array{code: string, description: string} */
    public function jsonSerialize(): array
    {
        return ['code' => $this->code, 'description' => $this->description];
    }
}
