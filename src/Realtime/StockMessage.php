<?php

declare(strict_types=1);

namespace Invezgo\Realtime;

use Invezgo\Internal\Hydrate;

/**
 * Full stock quote snapshot (`stock` channel); replace the previous one with it.
 * Fields the exchange has not provided yet are null.
 */
final class StockMessage
{
    /**
     * @param string     $code      Stock code.
     * @param float      $last      Last price.
     * @param float|null $prev      Previous close.
     * @param float|null $open      Open.
     * @param float|null $high      Day high.
     * @param float|null $low       Day low.
     * @param float|null $change    `last - prev`.
     * @param float|null $changePct Change in percent.
     * @param int|null   $volume    Volume today in shares (not lots).
     * @param int|null   $value     Value today in Rupiah.
     * @param int|null   $freq      Number of trades today.
     * @param float|null $avg       Volume-weighted average price today.
     * @param float|null $iep       Pre-opening Indicative Equilibrium Price.
     * @param int|null   $iev       Pre-opening Indicative Equilibrium Volume, in shares.
     * @param string     $time      Data time `HHMMSS` (WIB).
     * @param int|null   $seq       Exchange update sequence number.
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
        public readonly ?float $avg,
        public readonly ?float $iep,
        public readonly ?int $iev,
        public readonly string $time,
        public readonly ?int $seq,
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
            avg: Hydrate::float($data['avg'] ?? null),
            iep: Hydrate::float($data['iep'] ?? null),
            iev: Hydrate::int($data['iev'] ?? null),
            time: Hydrate::string($data['time'] ?? null) ?? '',
            seq: Hydrate::int($data['seq'] ?? null),
        );
    }
}
