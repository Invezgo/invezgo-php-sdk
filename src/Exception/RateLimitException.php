<?php

declare(strict_types=1);

namespace Invezgo\Exception;

/**
 * HTTP 429: every configured API key is rate limited (per-minute limit) or out of monthly quota.
 *
 * The SDK has already rotated through all additional API keys before throwing this.
 */
class RateLimitException extends ApiException
{
    /**
     * @param int|null $retryAfter    Seconds until the earliest key becomes usable again.
     * @param int|null $limit         X-RateLimit-Limit of the last 429 response (requests per minute).
     * @param int|null $remaining     X-RateLimit-Remaining of the last 429 response.
     * @param int|null $reset         X-RateLimit-Reset of the last 429 response (Unix seconds).
     * @param bool     $quotaExceeded True when the monthly quota (not the per-minute limit) was hit.
     */
    public function __construct(
        string $message,
        ?string $error = null,
        ?string $requestId = null,
        ?string $path = null,
        mixed $body = null,
        public readonly ?int $retryAfter = null,
        public readonly ?int $limit = null,
        public readonly ?int $remaining = null,
        public readonly ?int $reset = null,
        public readonly bool $quotaExceeded = false,
    ) {
        parent::__construct($message, 429, $error, $requestId, $path, $body);
    }
}
