<?php

declare(strict_types=1);

namespace Invezgo\Tests;

use Invezgo\Cache\LruCache;
use PHPUnit\Framework\TestCase;

final class LruCacheTest extends TestCase
{
    public function testEvictsLeastRecentlyUsedByEntryCount(): void
    {
        $cache = new LruCache(2, 1000);
        $cache->set('a', 'A', 60, 0);
        $cache->set('b', 'B', 60, 0);
        self::assertSame('A', $cache->get('a', 1)); // a is now most recently used
        $cache->set('c', 'C', 60, 0);

        self::assertNull($cache->get('b', 1));
        self::assertSame('A', $cache->get('a', 1));
        self::assertSame('C', $cache->get('c', 1));
        self::assertSame(2, $cache->count());
    }

    public function testEvictsByTotalBytesAndSkipsOversizedBodies(): void
    {
        $cache = new LruCache(100, 10);
        $cache->set('a', '12345', 60, 0);
        $cache->set('b', '12345', 60, 0);
        self::assertSame(10, $cache->bytes());

        $cache->set('c', '123', 60, 0);
        self::assertNull($cache->get('a', 0));
        self::assertSame(8, $cache->bytes());

        $cache->set('huge', str_repeat('x', 11), 60, 0);
        self::assertNull($cache->get('huge', 0));
        self::assertSame(8, $cache->bytes());
    }

    public function testExpiresEntriesAndReplacesExistingKeys(): void
    {
        $cache = new LruCache(10, 100);
        $cache->set('a', 'old', 5, 0);
        $cache->set('a', 'new', 5, 0);
        self::assertSame(3, $cache->bytes());
        self::assertSame('new', $cache->get('a', 4.9));
        self::assertNull($cache->get('a', 5.0));
        self::assertSame(0, $cache->count());
        self::assertSame(0, $cache->bytes());

        $cache->set('zero', 'x', 0, 0);
        self::assertSame(0, $cache->count());
    }

    public function testDeleteWhereAndClear(): void
    {
        $cache = new LruCache(10, 100);
        $cache->set('/watchlists', '1', 60, 0);
        $cache->set('/analysis/list/stock', '2', 60, 0);
        $cache->deleteWhere(static fn (string $k): bool => str_starts_with($k, '/watchlists'));
        self::assertSame(1, $cache->count());
        $cache->clear();
        self::assertSame(0, $cache->count());
        self::assertSame(0, $cache->bytes());
    }
}
