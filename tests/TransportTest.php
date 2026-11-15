<?php

declare(strict_types=1);

namespace Invezgo\Tests;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Invezgo\Cache\CacheConfig;
use Invezgo\Enum\FilterColumn;
use Invezgo\Exception\ApiException;
use Invezgo\Exception\AuthenticationException;
use Invezgo\Exception\ConnectionException;
use Invezgo\Exception\NotFoundException;
use Invezgo\Exception\PaymentRequiredException;
use Invezgo\Exception\RateLimitException;
use Invezgo\Exception\ServerException;
use Invezgo\Exception\ValidationException;
use Invezgo\InvezgoClient;
use Invezgo\Model\AddWatchlistRequest;
use Invezgo\Model\TopChangeResponse;

final class TransportTest extends TestCase
{
    private const STOCKS = [['code' => 'BBCA', 'name' => 'Bank Central Asia', 'sector' => 'Keuangan', 'logo' => null]];

    public function testBuildsCanonicalUrlAndHeaders(): void
    {
        $client = $this->client();
        $this->queue(200, ['gain' => [], 'loss' => []]);
        $this->queue(200, ['code' => 'BBCA']);

        $result = $client->analysis->getTopChange(
            filterValue: 5.0,
            filterColumn: FilterColumn::Change,
            filterOperator: '>=',
            date: new \DateTimeImmutable('2024-12-30'),
        );
        $client->analysis->getInformation('BB CA/');

        self::assertInstanceOf(TopChangeResponse::class, $result);
        $first = $this->request(0);
        self::assertSame('GET', $first->getMethod());
        self::assertSame('https://api.invezgo.com/analysis/top/change?date=2024-12-30&filter_column=change&filter_operator=%3E%3D&filter_value=5', (string) $first->getUri());
        self::assertSame('Bearer KEY_A', $first->getHeaderLine('Authorization'));
        self::assertSame('invezgo-php-sdk/' . InvezgoClient::VERSION, $first->getHeaderLine('User-Agent'));
        self::assertFalse($first->hasHeader('Content-Type'));
        self::assertSame('/analysis/information/BB%20CA%2F', $this->request(1)->getUri()->getPath());
    }

    public function testCachesGetResponsesUntilCleared(): void
    {
        $client = $this->client();
        $this->queue(200, self::STOCKS);
        $this->queue(200, self::STOCKS);

        $a = $client->analysis->getStockList();
        $b = $client->analysis->getStockList();
        self::assertCount(1, $this->history);
        self::assertEquals($a, $b);
        self::assertNotSame($a[0], $b[0], 'each hit decodes a fresh object');
        self::assertSame('BBCA', $b[0]->code);

        $client->clearCache();
        $client->analysis->getStockList();
        self::assertCount(2, $this->history);
    }

    public function testTtlTiers(): void
    {
        $client = $this->client(cache: new CacheConfig(ttl: 60, realtimeTtl: 5));
        $this->queue(200, self::STOCKS);
        $this->queue(200, ['code' => 'BBCA', 'bid' => [], 'offer' => []]);
        $this->queue(200, ['code' => 'BBCA', 'bid' => [], 'offer' => []]);
        $this->queue(200, self::STOCKS);

        $client->analysis->getStockList();
        $client->analysis->getOrderBook('BBCA', 'RG'); // [REALTIME]
        $this->now += 6;
        $client->analysis->getStockList();   // still fresh (60 s)
        $client->analysis->getOrderBook('BBCA', 'RG'); // expired (5 s)
        self::assertCount(3, $this->history);

        $this->now += 60;
        $client->analysis->getStockList();
        self::assertCount(4, $this->history);
    }

    public function testCacheCanBeDisabled(): void
    {
        $client = $this->client(cache: false);
        $this->queue(200, self::STOCKS);
        $this->queue(200, self::STOCKS);
        $client->analysis->getStockList();
        $client->analysis->getStockList();
        self::assertCount(2, $this->history);
    }

    public function testUsageEndpointsAreNeverCached(): void
    {
        $client = $this->client();
        $usage = ['usage' => 1, 'remaining' => 9, 'limit' => 10, 'isBlocked' => false, 'expire' => 'x', 'role' => 'ADVANCE'];
        $this->queue(200, $usage);
        $this->queue(200, $usage);
        $client->usage->getApiUsage();
        $client->usage->getApiUsage();
        self::assertCount(2, $this->history);
    }

    public function testMutationPurgesPersonalDataOnly(): void
    {
        $client = $this->client();
        $this->queue(200, []);                       // GET /watchlists
        $this->queue(200, self::STOCKS);             // GET /analysis/list/stock
        $this->queue(201, ['message' => 'ok']);      // POST /watchlists
        $this->queue(200, []);                       // GET /watchlists again

        $client->watchlists->list();
        $client->analysis->getStockList();
        $client->watchlists->add(new AddWatchlistRequest(group: str_repeat('g', 25), code: 'BBCA', price: 9900, scope: ['web']));
        $client->watchlists->list();
        $client->analysis->getStockList();

        self::assertCount(4, $this->history);
        $post = $this->request(2);
        self::assertSame('POST', $post->getMethod());
        self::assertSame('application/json', $post->getHeaderLine('Content-Type'));
        self::assertSame(
            ['group' => str_repeat('g', 25), 'code' => 'BBCA', 'price' => 9900, 'scope' => ['web']],
            json_decode((string) $post->getBody(), true),
        );
    }

    public function testArrayBodyIsAccepted(): void
    {
        $client = $this->client();
        $this->queue(200, ['message' => 'deleted']);
        $result = $client->watchlists->delete(['id' => ['a', 'b']]);
        self::assertSame('deleted', $result->message);
        self::assertSame('{"id":["a","b"]}', (string) $this->request(0)->getBody());
    }

    public function testRotatesKeyOnPerMinuteLimitPerRoute(): void
    {
        $client = $this->client(['KEY_B']);
        $this->queue(429, ['statusCode' => 429, 'message' => 'Too many requests: 250 requests per minute', 'error' => 'Too Many Requests'], ['Retry-After' => '30']);
        $this->queue(200, self::STOCKS);
        $this->queue(200, []);
        $this->queue(200, self::STOCKS);

        $client->analysis->getStockList();
        self::assertSame(['KEY_A', 'KEY_B'], [$this->keyOf(0), $this->keyOf(1)]);

        $client->watchlists->list();          // other route: primary key still usable
        self::assertSame('KEY_A', $this->keyOf(2));

        $this->now += 31;                     // block expired → primary preferred again
        $client->clearCache();
        $client->analysis->getStockList();
        self::assertSame('KEY_A', $this->keyOf(3));
    }

    public function testQuotaBlocksWholeKey(): void
    {
        $client = $this->client(['KEY_B']);
        $this->queue(429, ['statusCode' => 429, 'message' => 'Monthly quota exceeded', 'error' => 'Too Many Requests'], ['Retry-After' => '86400']);
        $this->queue(200, self::STOCKS);
        $this->queue(200, []);

        $client->analysis->getStockList();
        $client->watchlists->list();
        self::assertSame(['KEY_A', 'KEY_B', 'KEY_B'], [$this->keyOf(0), $this->keyOf(1), $this->keyOf(2)]);
    }

    public function testAllKeysBlockedThrowsWithoutExtraRequest(): void
    {
        $client = $this->client(['KEY_B', 'KEY_A', '']); // duplicates and blanks are ignored
        $headers = ['Retry-After' => '30', 'X-RateLimit-Limit' => '250', 'X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => '1999999999'];
        $this->queue(429, ['statusCode' => 429, 'message' => 'Too many requests per minute', 'error' => 'Too Many Requests', 'requestId' => 'r1'], $headers);
        $this->queue(429, ['statusCode' => 429, 'message' => 'Too many requests per minute', 'error' => 'Too Many Requests', 'requestId' => 'r2'], ['Retry-After' => '45']);

        try {
            $client->analysis->getStockList();
            self::fail('expected RateLimitException');
        } catch (RateLimitException $e) {
            self::assertCount(2, $this->history);
            self::assertSame(429, $e->status);
            self::assertSame(30, $e->retryAfter, 'smallest remaining block');
            self::assertSame('r2', $e->requestId);
            self::assertFalse($e->quotaExceeded);
        }

        $this->now += 10;
        try {
            $client->analysis->getStockList();
            self::fail('expected RateLimitException');
        } catch (RateLimitException $e) {
            self::assertCount(2, $this->history, 'no request is sent while every key is blocked');
            self::assertSame(20, $e->retryAfter);
        }
    }

    public function testFallsBackToRateLimitResetHeader(): void
    {
        $client = $this->client();
        $this->queue(429, ['statusCode' => 429, 'message' => 'slow down', 'error' => 'Too Many Requests'], [
            'X-RateLimit-Reset' => (string) (time() + 120),
            'X-RateLimit-Limit' => '250',
        ]);
        try {
            $client->analysis->getStockList();
            self::fail('expected RateLimitException');
        } catch (RateLimitException $e) {
            self::assertGreaterThanOrEqual(118, $e->retryAfter);
            self::assertLessThanOrEqual(120, $e->retryAfter);
            self::assertSame(250, $e->limit);
        }
    }

    /**
     * @return iterable<string, array{int, class-string<ApiException>}>
     */
    public static function errorStatuses(): iterable
    {
        yield '400' => [400, ValidationException::class];
        yield '422' => [422, ValidationException::class];
        yield '401' => [401, AuthenticationException::class];
        yield '402' => [402, PaymentRequiredException::class];
        yield '404' => [404, NotFoundException::class];
        yield '409' => [409, ApiException::class];
        yield '500' => [500, ServerException::class];
        yield '503' => [503, ServerException::class];
    }

    /**
     * @param class-string<ApiException> $class
     * @dataProvider errorStatuses
     */
    public function testMapsErrorStatuses(int $status, string $class): void
    {
        $client = $this->client();
        $this->queue($status, [
            'statusCode' => $status,
            'message' => ['`code` is required', '`date` is invalid'],
            'error' => 'Some Error',
            'path' => '/analysis/list/stock',
            'requestId' => 'req-1',
        ]);
        try {
            $client->analysis->getStockList();
            self::fail('expected exception');
        } catch (ApiException $e) {
            self::assertSame($class, $e::class);
            self::assertSame($status, $e->status);
            self::assertSame($status, $e->getCode());
            self::assertSame('`code` is required; `date` is invalid', $e->getMessage());
            self::assertSame('Some Error', $e->error);
            self::assertSame('req-1', $e->requestId);
            self::assertSame('/analysis/list/stock', $e->path);
            self::assertIsArray($e->body);
        }
    }

    public function testNonJsonErrorBody(): void
    {
        $client = $this->client();
        $this->queue(502, 'Bad gateway');
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Bad gateway');
        $client->analysis->getStockList();
    }

    public function testConnectionErrors(): void
    {
        $client = $this->client();
        $this->mock->append(new ConnectException('timed out', new Request('GET', '/')));
        $this->expectException(ConnectionException::class);
        $client->analysis->getStockList();
    }

    public function testNullAndEmptyBodies(): void
    {
        $client = $this->client();
        $this->queue(200, 'null');
        $this->queue(200, '');
        self::assertNull($client->analysis->getShareholderClassifyTable('BBCA'));
        self::assertSame([], $client->analysis->getNotation());
    }

    public function testRequiresApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new InvezgoClient('  ');
    }
}
