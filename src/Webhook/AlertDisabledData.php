<?php

declare(strict_types=1);

namespace Invezgo\Webhook;

use Invezgo\Internal\Hydrate;

/**
 * `data` of `alert.disabled`: a Live Alert was switched off automatically.
 */
final class AlertDisabledData
{
    public function __construct(
        public readonly string $alertId,
        public readonly string $alertName,
        public readonly string $reason,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            alertId: Hydrate::string($data['alert_id'] ?? null) ?? '',
            alertName: Hydrate::string($data['alert_name'] ?? null) ?? '',
            reason: Hydrate::string($data['reason'] ?? null) ?? '',
        );
    }
}
