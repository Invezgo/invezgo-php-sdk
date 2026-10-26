<?php

declare(strict_types=1);

namespace Invezgo\Cache;

/**
 * Tuning for the in-memory (L1) LRU response cache.
 *
 * Pass `cache: false` to {@see \Invezgo\InvezgoClient} to disable caching entirely.
 */
final class CacheConfig
{
    /**
     * @param float $ttl         Seconds to keep normal GET responses. <= 0 disables that tier.
     * @param float $realtimeTtl Seconds to keep responses of [REALTIME] endpoints. <= 0 disables that tier.
     * @param int   $maxEntries  Maximum number of cached responses (least recently used are evicted first).
     * @param int   $maxBytes    Maximum total size of cached raw response bodies, in bytes (default 32 MiB).
     */
    public function __construct(
        public readonly float $ttl = 60,
        public readonly float $realtimeTtl = 5,
        public readonly int $maxEntries = 1000,
        public readonly int $maxBytes = 33554432,
    ) {
        if ($maxEntries < 1 || $maxBytes < 1) {
            throw new \InvalidArgumentException('maxEntries and maxBytes must be positive');
        }
    }
}
