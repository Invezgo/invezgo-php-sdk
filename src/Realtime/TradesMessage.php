<?php

declare(strict_types=1);

namespace Invezgo\Realtime;

use Invezgo\Internal\Hydrate;

/**
 * A batch of running trades, in exchange order.
 */
final class TradesMessage
{
    /**
     * @param int         $ts   Server send time, Unix milliseconds (compare with your clock to measure delay).
     * @param list<Trade> $data Trades for your subscribed codes that pass the size filters.
     */
    public function __construct(
        public readonly int $ts,
        public readonly array $data,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ts: Hydrate::int($data['ts'] ?? null) ?? 0,
            data: Hydrate::list($data['data'] ?? null, static fn (mixed $x) => Trade::fromArray(Hydrate::array($x) ?? [])) ?? [],
        );
    }
}
