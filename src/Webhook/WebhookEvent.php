<?php

declare(strict_types=1);

namespace Invezgo\Webhook;

use Invezgo\Internal\Hydrate;

/**
 * One webhook delivery. `domain` and `type` stay plain strings so new events never break decoding.
 */
final class WebhookEvent
{
    /**
     * @param string               $id        Delivery ID (same as X-Invezgo-Delivery); stable across retries, use it to dedupe.
     * @param string               $domain    Event group: "test", "quota", "update", "alert", ...
     * @param string               $type      Event type within the domain, e.g. "summary" or "triggered".
     * @param int                  $createdAt Unix time (seconds) the event was created.
     * @param array<string, mixed> $data      Event-specific payload; see {@see self::typedData()}.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $domain,
        public readonly string $type,
        public readonly int $createdAt,
        public readonly array $data,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Hydrate::string($data['id'] ?? null) ?? '',
            domain: Hydrate::string($data['domain'] ?? null) ?? '',
            type: Hydrate::string($data['type'] ?? null) ?? '',
            createdAt: Hydrate::int($data['created_at'] ?? null) ?? 0,
            data: Hydrate::array($data['data'] ?? null) ?? [],
        );
    }

    /** "{domain}.{type}", e.g. "update.summary" (same as the X-Invezgo-Event header). */
    public function name(): string
    {
        return $this->domain . '.' . $this->type;
    }

    /**
     * Typed `data` for the documented events, or null for an event this SDK version does not know.
     */
    public function typedData(): UpdateData|AlertTriggeredData|AlertDisabledData|PingData|QuotaData|null
    {
        return match ($this->name()) {
            'update.summary', 'update.notation', 'update.news', 'update.disclosure', 'update.financial',
            'update.five', 'update.one', 'update.classification', 'update.ksei', 'update.hsc',
            'update.shareholder', 'update.insider', 'update.uma', 'update.suspend', 'update.calendar'
                => UpdateData::fromArray($this->data),
            'alert.triggered' => AlertTriggeredData::fromArray($this->data),
            'alert.disabled' => AlertDisabledData::fromArray($this->data),
            'test.ping' => PingData::fromArray($this->data),
            'quota.low', 'quota.exhausted' => QuotaData::fromArray($this->data),
            default => null,
        };
    }
}
