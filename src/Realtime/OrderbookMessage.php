<?php

declare(strict_types=1);

namespace Invezgo\Realtime;

use Invezgo\Internal\Hydrate;

/**
 * The full set of levels for one side of a stock's order book; replace the previous side with it.
 */
final class OrderbookMessage
{
    /**
     * @param string     $code   Stock code.
     * @param string     $side   "bid" or "offer".
     * @param list<mixed> $levels Price levels as decoded JSON (the element shape is not documented yet).
     * @param int|null   $seq    Exchange update sequence number, may be null.
     */
    public function __construct(
        public readonly string $code,
        public readonly string $side,
        public readonly array $levels,
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
            side: Hydrate::string($data['side'] ?? null) ?? '',
            levels: Hydrate::list($data['levels'] ?? null, static fn (mixed $x): mixed => $x) ?? [],
            seq: Hydrate::int($data['seq'] ?? null),
        );
    }
}
