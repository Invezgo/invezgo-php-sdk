<?php

declare(strict_types=1);

namespace Invezgo\Webhook;

use Invezgo\Internal\Hydrate;

/**
 * A stock entering or leaving a Live Alert formula.
 */
final class AlertStock
{
    /**
     * @param string $code  Stock code.
     * @param float  $price Last price when the change was detected.
     */
    public function __construct(
        public readonly string $code,
        public readonly float $price,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: Hydrate::string($data['code'] ?? null) ?? '',
            price: Hydrate::float($data['price'] ?? null) ?? 0.0,
        );
    }
}
