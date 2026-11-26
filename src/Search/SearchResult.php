<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr\Search;

use AthenaNetworks\Abr\Abn;
use AthenaNetworks\Abr\AbnStatus;
use AthenaNetworks\Abr\NameType;
use AthenaNetworks\Abr\State;
use JsonSerializable;

/**
 * One match from a name search.
 *
 * This is a summary only. Pass $abn to AbrClient::lookupAbn() for the full record.
 */
final readonly class SearchResult implements JsonSerializable
{
    public function __construct(
        public Abn $abn,
        public AbnStatus $abnStatus,
        public string $name,
        public NameType $nameType,
        /** Match score, 0-100. */
        public int $score,
        /** Whether the matched name is still current for this ABN. */
        public bool $isCurrentName,
        public ?State $state,
        public ?string $postcode,
    ) {
    }

    public function isActive(): bool
    {
        return $this->abnStatus === AbnStatus::Active;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'abn' => $this->abn->value,
            'abn_status' => $this->abnStatus->value,
            'name' => $this->name,
            'name_type' => $this->nameType->value,
            'score' => $this->score,
            'is_current_name' => $this->isCurrentName,
            'state' => $this->state?->value,
            'postcode' => $this->postcode,
        ];
    }
}
