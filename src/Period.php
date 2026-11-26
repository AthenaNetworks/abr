<?php

declare(strict_types=1);

namespace AthenaNetworks\Abr;

use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;

/**
 * A date range during which a record (name, status, address, ...) was in effect.
 *
 * A null "to" date means the record is still current. A null "from" date means the ABR gave no start date.
 */
final readonly class Period implements JsonSerializable
{
    public function __construct(
        public ?DateTimeImmutable $from,
        public ?DateTimeImmutable $to = null,
    ) {
    }

    public function isCurrent(): bool
    {
        return $this->to === null;
    }

    public function contains(DateTimeInterface $date): bool
    {
        $day = $date->format('Y-m-d');

        return ($this->from === null || $this->from->format('Y-m-d') <= $day)
            && ($this->to === null || $this->to->format('Y-m-d') >= $day);
    }

    /** @return array{from: ?string, to: ?string} */
    public function jsonSerialize(): array
    {
        return [
            'from' => $this->from?->format('Y-m-d'),
            'to' => $this->to?->format('Y-m-d'),
        ];
    }
}
