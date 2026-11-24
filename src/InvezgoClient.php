<?php

declare(strict_types=1);

namespace Invezgo;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Invezgo\Cache\CacheConfig;
use Invezgo\Cache\LruCache;
use Invezgo\Http\Transport;
use Invezgo\Realtime\RealtimeClient;
use Invezgo\Resource\AlertsResource;
use Invezgo\Resource\AnalysisResource;
use Invezgo\Resource\BatchResource;
use Invezgo\Resource\JournalsResource;
use Invezgo\Resource\PortfoliosResource;
use Invezgo\Resource\PostsResource;
use Invezgo\Resource\ScreenerResource;
use Invezgo\Resource\SearchResource;
use Invezgo\Resource\TradesResource;
use Invezgo\Resource\UsageResource;
use Invezgo\Resource\WatchlistsResource;

/**
 * Entry point of the Invezgo API SDK.
 *
 * ```php
 * $client = new InvezgoClient('YOUR_API_KEY', additionalApiKeys: ['BACKUP_KEY']);
 * $stocks = $client->analysis->getStockList();
 * ```
 *
 * One instance keeps the in-memory cache and the API key rotation state, so create it once and reuse it
 * (e.g. as a singleton in your container).
 */
final class InvezgoClient
{
    public const VERSION = '2.3.0';

    public const DEFAULT_BASE_URL = 'https://api.invezgo.com';

    /** Stock market data: lists, prices, charts, broker flow, shareholders, fundamentals. */
    public readonly AnalysisResource $analysis;

    /** Fetch many stocks in one request (counted as 1 request). */
    public readonly BatchResource $batch;

    /** Search stocks and users. */
    public readonly SearchResource $search;

    /** Run stock screeners and manage saved presets. */
    public readonly ScreenerResource $screener;

    /** API usage and quota (does not consume quota). */
    public readonly UsageResource $usage;

    /** Personal watchlists and watchlist groups. */
    public readonly WatchlistsResource $watchlists;

    /** Personal trading journal. */
    public readonly JournalsResource $journals;

    /** Personal portfolio built from the journal. */
    public readonly PortfoliosResource $portfolios;

    /** Live formula alerts. */
    public readonly AlertsResource $alerts;

    /** Community posts, comments, likes and votes. */
    public readonly PostsResource $posts;

    /** Personal realized trades. */
    public readonly TradesResource $trades;

    private readonly Transport $transport;

    private readonly string $apiKey;

    private readonly string $baseUrl;

    /**
     * @param string                $apiKey            Primary API key (https://invezgo.com/id/setting/api).
     * @param list<string>          $additionalApiKeys Fallback keys, used in order when a key gets HTTP 429
     *                                                 (per-minute rate limit or monthly quota exhausted).
     * @param string|null           $baseUrl           API base URL, default https://api.invezgo.com.
     * @param float                 $timeout           Timeout per HTTP attempt, in seconds.
     * @param bool|CacheConfig      $cache             true = in-memory LRU cache with defaults, false = off,
     *                                                 or a {@see CacheConfig} to tune TTLs and memory limits.
     * @param ClientInterface|null  $httpClient        Custom Guzzle client (proxy, middleware, mocking).
     *
     * @throws \InvalidArgumentException When no API key is given or the timeout is not positive.
     */
    public function __construct(
        string $apiKey,
        array $additionalApiKeys = [],
        ?string $baseUrl = null,
        float $timeout = 30,
        bool|CacheConfig $cache = true,
        ?ClientInterface $httpClient = null,
    ) {
        if (trim($apiKey) === '') {
            throw new \InvalidArgumentException('An Invezgo API key is required.');
        }
        if ($timeout <= 0) {
            throw new \InvalidArgumentException('Timeout must be greater than 0.');
        }
        $keys = [trim($apiKey)];
        foreach ($additionalApiKeys as $key) {
            $key = trim($key);
            if ($key !== '' && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }
        $config = $cache instanceof CacheConfig ? $cache : new CacheConfig();
        $this->apiKey = $keys[0];
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');

        $this->transport = new Transport(
            $keys,
            $this->baseUrl,
            $timeout,
            $httpClient ?? new Client(),
            $cache === false ? null : new LruCache($config->maxEntries, $config->maxBytes),
            $config,
            'invezgo-php-sdk/' . self::VERSION,
        );

        $this->analysis = new AnalysisResource($this->transport);
        $this->batch = new BatchResource($this->transport);
        $this->search = new SearchResource($this->transport);
        $this->screener = new ScreenerResource($this->transport);
        $this->usage = new UsageResource($this->transport);
        $this->watchlists = new WatchlistsResource($this->transport);
        $this->journals = new JournalsResource($this->transport);
        $this->portfolios = new PortfoliosResource($this->transport);
        $this->alerts = new AlertsResource($this->transport);
        $this->posts = new PostsResource($this->transport);
        $this->trades = new TradesResource($this->transport);
    }

    /** Drop every cached response. */
    public function clearCache(): void
    {
        $this->transport->clearCache();
    }

    /**
     * WebSocket client for real-time trades and order book (Elite plan), using the primary API key and
     * `baseUrl` with https→wss / http→ws + `/ws`. Requires `composer require phrity/websocket:^3.0`.
     *
     * @param bool $reconnect Reconnect automatically after a lost connection.
     *
     * @throws Exception\InvezgoException When phrity/websocket is not installed.
     */
    public function realtime(bool $reconnect = true): RealtimeClient
    {
        return new RealtimeClient($this->apiKey, (string) preg_replace('#^http#i', 'ws', $this->baseUrl) . '/ws', $reconnect);
    }
}
