<?php

declare(strict_types=1);

namespace Invezgo\Cache;

/**
 * Least-recently-used cache of raw response bodies, bounded by entry count and total bytes.
 *
 * PHP arrays keep insertion order, so the first key is the least recently used one and touching an
 * entry is unset + re-insert: every operation is O(1) amortised. Expired entries are dropped lazily on
 * read or when evicted. Callers pass the (monotonic) time, which keeps the class deterministic.
 *
 * @internal
 */
final class LruCache
{
    /** @var array<string, array{body: string, expires: float}> */
    private array $entries = [];

    private int $bytes = 0;

    public function __construct(private readonly int $maxEntries, private readonly int $maxBytes)
    {
    }

    public function get(string $key, float $now): ?string
    {
        if (!isset($this->entries[$key])) {
            return null;
        }
        $entry = $this->entries[$key];
        unset($this->entries[$key]);
        if ($entry['expires'] <= $now) {
            $this->bytes -= strlen($entry['body']);
            return null;
        }
        $this->entries[$key] = $entry;
        return $entry['body'];
    }

    public function set(string $key, string $body, float $ttl, float $now): void
    {
        $this->delete($key);
        $size = strlen($body);
        if ($ttl <= 0 || $size > $this->maxBytes) {
            return;
        }
        $this->entries[$key] = ['body' => $body, 'expires' => $now + $ttl];
        $this->bytes += $size;
        while (count($this->entries) > $this->maxEntries || $this->bytes > $this->maxBytes) {
            $this->delete((string) array_key_first($this->entries));
        }
    }

    public function delete(string $key): void
    {
        if (isset($this->entries[$key])) {
            $this->bytes -= strlen($this->entries[$key]['body']);
            unset($this->entries[$key]);
        }
    }

    /**
     * @param callable(string): bool $predicate
     */
    public function deleteWhere(callable $predicate): void
    {
        foreach (array_keys($this->entries) as $key) {
            if ($predicate((string) $key)) {
                $this->delete((string) $key);
            }
        }
    }

    public function clear(): void
    {
        $this->entries = [];
        $this->bytes = 0;
    }

    public function count(): int
    {
        return count($this->entries);
    }

    public function bytes(): int
    {
        return $this->bytes;
    }
}
