<?php

declare(strict_types=1);

namespace Invezgo\Http;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Invezgo\Cache\CacheConfig;
use Invezgo\Cache\LruCache;
use Invezgo\Exception\ApiException;
use Invezgo\Exception\AuthenticationException;
use Invezgo\Exception\ConnectionException;
use Invezgo\Exception\InvezgoException;
use Invezgo\Exception\NotFoundException;
use Invezgo\Exception\PaymentRequiredException;
use Invezgo\Exception\RateLimitException;
use Invezgo\Exception\ServerException;
use Invezgo\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;

/**
 * Sends requests: canonical URL building, L1 cache, API key rotation on 429 and error mapping.
 *
 * @internal Use {@see \Invezgo\InvezgoClient} instead.
 */
final class Transport
{
    /** Path segments holding per-account data; a successful mutation purges their cache entries. */
    public const PERSONAL_SEGMENTS = ['watchlists', 'journals', 'portfolios', 'trades', 'alerts', 'screener'];

    private const QUOTA_PATTERN = '/quota|kuota|monthly|bulanan/i';

    /**
     * Monotonic clock in seconds; replaceable in tests.
     *
     * @var \Closure(): float
     */
    public \Closure $clock;

    /** @var non-empty-list<string> */
    private array $keys;

    /** @var array<string, float> "key:<index>" (quota) or "key:<index> <route>" (per-minute) => blocked until */
    private array $blocks = [];

    /**
     * @param non-empty-list<string> $keys Primary key first, then additional keys.
     */
    public function __construct(
        array $keys,
        private readonly string $baseUrl,
        private readonly float $timeout,
        private readonly ClientInterface $http,
        private readonly ?LruCache $cache,
        private readonly CacheConfig $cacheConfig,
        private readonly string $userAgent,
    ) {
        $this->keys = $keys;
        $this->clock = static fn (): float => hrtime(true) / 1e9;
    }

    /**
     * @param array<string, mixed> $pathParams
     * @param array<string, mixed> $query
     * @param string $cache One of "default", "realtime", "none".
     * @return mixed Decoded JSON (associative arrays), or null for an empty/`null` body.
     * @throws InvezgoException
     */
    public function request(string $method, string $route, array $pathParams, array $query, mixed $body, string $cache): mixed
    {
        $path = preg_replace_callback(
            '/\{(\w+)\}/',
            fn (array $m): string => rawurlencode(self::scalar($pathParams[$m[1]] ?? '')),
            $route,
        );
        $params = [];
        foreach ($query as $name => $value) {
            if ($value !== null) {
                $params[$name] = self::scalar($value);
            }
        }
        ksort($params, SORT_STRING);
        $key = $path . ($params ? '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986) : '');

        $ttl = match ($cache) {
            'default' => $this->cacheConfig->ttl,
            'realtime' => $this->cacheConfig->realtimeTtl,
            default => 0.0,
        };
        $cacheable = $this->cache !== null && $method === 'GET' && $ttl > 0;
        if ($cacheable) {
            $hit = $this->cache->get($key, ($this->clock)());
            if ($hit !== null) {
                return self::decode($hit);
            }
        }

        $raw = $this->send($method, $route, $key, $body);
        $data = self::decode($raw);

        if ($cacheable) {
            $this->cache->set($key, $raw, $ttl, ($this->clock)());
        } elseif ($method !== 'GET' && $this->cache !== null) {
            $this->cache->deleteWhere(
                static fn (string $k): bool => in_array(preg_split('#[/?]#', $k)[1] ?? '', self::PERSONAL_SEGMENTS, true),
            );
        }
        return $data;
    }

    public function clearCache(): void
    {
        $this->cache?->clear();
    }

    /**
     * Send with the first usable key, rotating to the next one on HTTP 429.
     *
     * @throws InvezgoException
     */
    private function send(string $method, string $route, string $pathAndQuery, mixed $body): string
    {
        $headers = ['Accept' => 'application/json', 'User-Agent' => $this->userAgent];
        $options = ['http_errors' => false, 'timeout' => $this->timeout];
        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
            $options['body'] = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $tried = [];
        $last = null;
        while (true) {
            $now = ($this->clock)();
            $index = null;
            foreach ($this->keys as $i => $_) {
                if (!isset($tried[$i]) && $this->blockedFor($i, $route, $now) <= 0) {
                    $index = $i;
                    break;
                }
            }
            if ($index === null) {
                throw $this->allKeysLimited($route, $now, $last);
            }
            $tried[$index] = true;

            $options['headers'] = $headers + ['Authorization' => 'Bearer ' . $this->keys[$index]];
            try {
                $response = $this->http->request($method, $this->baseUrl . $pathAndQuery, $options);
            } catch (GuzzleException $e) {
                throw new ConnectionException('Request to Invezgo API failed: ' . $e->getMessage(), 0, $e);
            }

            $status = $response->getStatusCode();
            $raw = (string) $response->getBody();
            if ($status >= 200 && $status < 300) {
                return $raw;
            }
            $error = self::error($status, $raw, $response);
            if (!$error instanceof RateLimitException) {
                throw $error;
            }
            $wait = $error->retryAfter ?? 60;
            $scope = $error->quotaExceeded ? "key:{$index}" : "key:{$index} {$route}";
            $this->blocks[$scope] = ($this->clock)() + $wait;
            $last = $error;
        }
    }

    /** Seconds until key $i may call $route again (<= 0 when usable). */
    private function blockedFor(int $i, string $route, float $now): float
    {
        return max($this->blocks["key:{$i}"] ?? 0.0, $this->blocks["key:{$i} {$route}"] ?? 0.0) - $now;
    }

    private function allKeysLimited(string $route, float $now, ?RateLimitException $last): RateLimitException
    {
        $waits = array_map(fn (int $i): float => $this->blockedFor($i, $route, $now), array_keys($this->keys));
        $retryAfter = max(1, (int) ceil(min($waits)));
        if ($last === null) {
            return new RateLimitException(
                "All API keys are rate limited; retry in {$retryAfter} seconds.",
                'Too Many Requests',
                retryAfter: $retryAfter,
            );
        }
        return new RateLimitException(
            $last->getMessage(),
            $last->error,
            $last->requestId,
            $last->path,
            $last->body,
            $retryAfter,
            $last->limit,
            $last->remaining,
            $last->reset,
            $last->quotaExceeded,
        );
    }

    private static function error(int $status, string $raw, ResponseInterface $response): ApiException
    {
        try {
            $body = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $body = $raw;
        }
        $fields = is_array($body) ? $body : [];
        $message = $fields['message'] ?? null;
        if (is_array($message)) {
            $message = implode('; ', array_map(self::scalar(...), $message));
        }
        if (!is_string($message) || $message === '') {
            $message = $raw !== '' && !is_array($body) ? $raw : $response->getReasonPhrase();
        }
        $error = isset($fields['error']) && is_string($fields['error']) ? $fields['error'] : null;
        $requestId = isset($fields['requestId']) && is_string($fields['requestId']) ? $fields['requestId'] : null;
        $path = isset($fields['path']) && is_string($fields['path']) ? $fields['path'] : null;

        if ($status === 429) {
            $header = static fn (string $name): ?int => is_numeric($v = $response->getHeaderLine($name)) ? (int) $v : null;
            $retryAfter = $header('Retry-After');
            $reset = $header('X-RateLimit-Reset');
            if ($retryAfter === null || $retryAfter <= 0) {
                $retryAfter = $reset !== null && $reset - time() > 0 ? $reset - time() : 60;
            }
            return new RateLimitException(
                $message,
                $error,
                $requestId,
                $path,
                $body,
                $retryAfter,
                $header('X-RateLimit-Limit'),
                $header('X-RateLimit-Remaining'),
                $reset,
                preg_match(self::QUOTA_PATTERN, $message) === 1,
            );
        }
        $class = match (true) {
            $status === 400, $status === 422 => ValidationException::class,
            $status === 401 => AuthenticationException::class,
            $status === 402 => PaymentRequiredException::class,
            $status === 404 => NotFoundException::class,
            $status >= 500 => ServerException::class,
            default => ApiException::class,
        };
        return new $class($message, $status, $error, $requestId, $path, $body);
    }

    /** @throws ConnectionException */
    private static function decode(string $raw): mixed
    {
        $trimmed = trim($raw);
        if ($trimmed === '' || $trimmed === 'null') {
            return null;
        }
        try {
            return json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ConnectionException('Invezgo API returned a response that is not valid JSON.', 0, $e);
        }
    }

    /** Canonical string form of a path/query value. */
    private static function scalar(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        } elseif ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_float($value)) {
            return floor($value) === $value && abs($value) < 1e15 ? (string) (int) $value : json_encode($value, JSON_THROW_ON_ERROR);
        }
        return is_scalar($value) ? (string) $value : '';
    }
}
