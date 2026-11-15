<?php

declare(strict_types=1);

namespace Invezgo\Realtime;

use Invezgo\Internal\Hydrate;

/**
 * One running-trade row.
 */
final class Trade
{
    /**
     * @param string $id     Exchange trade ID, kept as a string (exceeds the JS safe integer); use it to dedupe.
     * @param string $time   Trade time "HHMMSS" (WIB).
     * @param string $code   Stock code.
     * @param float  $price  Trade price.
     * @param int    $lot    Volume in lots.
     * @param int    $value  Value in Rupiah (price x lot x 100).
     * @param float  $change Price change against the previous close.
     * @param string $side   "buy" (HAKA) or "sell" (HAKI).
     */
    public function __construct(
        public readonly string $id,
        public readonly string $time,
        public readonly string $code,
        public readonly float $price,
        public readonly int $lot,
        public readonly int $value,
        public readonly float $change,
        public readonly string $side,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Hydrate::string($data['id'] ?? null) ?? '',
            time: Hydrate::string($data['time'] ?? null) ?? '',
            code: Hydrate::string($data['code'] ?? null) ?? '',
            price: Hydrate::float($data['price'] ?? null) ?? 0.0,
            lot: Hydrate::int($data['lot'] ?? null) ?? 0,
            value: Hydrate::int($data['value'] ?? null) ?? 0,
            change: Hydrate::float($data['change'] ?? null) ?? 0.0,
            side: Hydrate::string($data['side'] ?? null) ?? '',
        );
    }
}
