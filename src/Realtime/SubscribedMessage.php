<?php

declare(strict_types=1);

namespace Invezgo\Realtime;

use Invezgo\Internal\Hydrate;

/**
 * The server's current subscription for one channel, sent after every subscribe/unsubscribe.
 */
final class SubscribedMessage
{
    /**
     * @param string            $channel  "trades", "orderbook", "stock" or "index".
     * @param list<string>|'*'  $codes    Subscribed codes, or "*" for every stock or index.
     * @param int|null          $minLot   Active lot filter (trades only).
     * @param int|null          $minValue Active value filter in Rupiah (trades only).
     */
    public function __construct(
        public readonly string $channel,
        public readonly array|string $codes,
        public readonly ?int $minLot = null,
        public readonly ?int $minValue = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $codes = ($data['codes'] ?? null) === '*'
            ? '*'
            : Hydrate::list($data['codes'] ?? null, static fn (mixed $x) => Hydrate::string($x) ?? '') ?? [];
        return new self(
            channel: Hydrate::string($data['channel'] ?? null) ?? '',
            codes: $codes,
            minLot: Hydrate::int($data['min_lot'] ?? null),
            minValue: Hydrate::int($data['min_value'] ?? null),
        );
    }
}
