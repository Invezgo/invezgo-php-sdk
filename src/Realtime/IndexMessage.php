<?php

declare(strict_types=1);

namespace Invezgo\Realtime;

use Invezgo\Internal\Hydrate;

/**
 * Index snapshot (`index` channel); replace the previous one with it. `code` keeps the exchange's casing
 * (e.g. `Investor33`), so compare it case-insensitively (`strcasecmp`).
 */
final class IndexMessage
{
    /**
     * @param string     $code      Index code, e.g. `COMPOSITE`.
     * @param float      $last      Last value.
     * @param float|null $prev      Previous close.
     * @param float|null $open      Open.
     * @param float|null $high      Day high.
     * @param float|null $low       Day low.
     * @param float|null $change    `last - prev`.
     * @param float|null $changePct Change in percent (3 decimals).
     * @param int|null   $volume    Volume today in shares.
     * @param int|null   $value     Value today in Rupiah.
     * @param int|null   $freq      Number of trades today.
     * @param string     $time      Data time `HHMMSS` (WIB).
     */
    public function __construct(
        public readonly string $code,
        public readonly float $last,
        public readonly ?float $prev,
        public readonly ?float $open,
        public readonly ?float $high,
        public readonly ?float $low,
        public readonly ?float $change,
        public readonly ?float $changePct,
        public readonly ?int $volume,
        public readonly ?int $value,
        public readonly ?int $freq,
        public readonly string $time,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: Hydrate::string($data['code'] ?? null) ?? '',
            last: Hydrate::float($data['last'] ?? null) ?? 0.0,
            prev: Hydrate::float($data['prev'] ?? null),
            open: Hydrate::float($data['open'] ?? null),
            high: Hydrate::float($data['high'] ?? null),
            low: Hydrate::float($data['low'] ?? null),
            change: Hydrate::float($data['change'] ?? null),
            changePct: Hydrate::float($data['change_pct'] ?? null),
            volume: Hydrate::int($data['volume'] ?? null),
            value: Hydrate::int($data['value'] ?? null),
            freq: Hydrate::int($data['freq'] ?? null),
            time: Hydrate::string($data['time'] ?? null) ?? '',
        );
    }
}
