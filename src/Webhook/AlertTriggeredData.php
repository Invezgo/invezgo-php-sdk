<?php

declare(strict_types=1);

namespace Invezgo\Webhook;

use Invezgo\Internal\Hydrate;

/**
 * `data` of `alert.triggered`: stocks that entered (IN) or left (OUT) a Live Alert formula.
 */
final class AlertTriggeredData
{
    /**
     * @param list<AlertStock> $stocksIn
     * @param list<AlertStock> $stocksOut
     */
    public function __construct(
        public readonly string $alertId,
        public readonly string $alertName,
        public readonly array $stocksIn,
        public readonly array $stocksOut,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $stock = static fn (mixed $x): AlertStock => AlertStock::fromArray(Hydrate::array($x) ?? []);
        return new self(
            alertId: Hydrate::string($data['alert_id'] ?? null) ?? '',
            alertName: Hydrate::string($data['alert_name'] ?? null) ?? '',
            stocksIn: Hydrate::list($data['stocks_in'] ?? null, $stock) ?? [],
            stocksOut: Hydrate::list($data['stocks_out'] ?? null, $stock) ?? [],
        );
    }
}
