<?php

declare(strict_types=1);

namespace Invezgo\Webhook;

use Invezgo\Internal\Hydrate;

/**
 * `data` of `quota.low` (95% used) and `quota.exhausted` (100% used).
 */
final class QuotaData
{
    /**
     * @param int|null $resetAt Unix time (seconds) the monthly quota resets, or null.
     */
    public function __construct(
        public readonly string $plan,
        public readonly int $limit,
        public readonly int $used,
        public readonly int $remaining,
        public readonly ?int $resetAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            plan: Hydrate::string($data['plan'] ?? null) ?? '',
            limit: Hydrate::int($data['limit'] ?? null) ?? 0,
            used: Hydrate::int($data['used'] ?? null) ?? 0,
            remaining: Hydrate::int($data['remaining'] ?? null) ?? 0,
            resetAt: Hydrate::int($data['reset_at'] ?? null),
        );
    }
}
