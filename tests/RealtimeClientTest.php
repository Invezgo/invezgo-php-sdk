<?php

declare(strict_types=1);

namespace Invezgo\Tests;

use GuzzleHttp\Psr7\Response;
use Invezgo\Exception\AuthenticationException;
use Invezgo\Exception\ConnectionException;
use Invezgo\Exception\PaymentRequiredException;
use Invezgo\InvezgoClient;
use Invezgo\Realtime\IndexMessage;
use Invezgo\Realtime\OrderbookMessage;
use Invezgo\Realtime\StockMessage;
use Invezgo\Realtime\RealtimeClient;
use Invezgo\Realtime\Trade;
use Invezgo\Realtime\TradesMessage;
use WebSocket\Client;
use WebSocket\Exception\ClientException;
use WebSocket\Exception\HandshakeException;
use WebSocket\Message\Close;

final class RealtimeClientTest extends \PHPUnit\Framework\TestCase
{
    private const CONNECTED = ['type' => 'connected'];

    /** @var list<float> */
    private array $sleeps = [];

    /** @var list<array{string, array<string, string>}> */
    private array $connects = [];

    /**
     * @param list<FakeSocket|\Throwable> $sockets Returned (or thrown) by successive connection attempts.
     */
    private function realtime(array $sockets, bool $reconnect = true, ?RealtimeClient $ws = null): RealtimeClient
    {
        $ws ??= new RealtimeClient('KEY_A', reconnect: $reconnect);
        /** @param array<string, string> $headers */
        $ws->connector = function (string $url, array $headers) use (&$sockets): Client {
            $this->connects[] = [$url, $headers];
            $next = array_shift($sockets);
            if ($next === null) {
                self::fail('Unexpected connection attempt');
            }
            if ($next instanceof \Throwable) {
                throw $next;
            }
            return $next;
        };
        $ws->sleep = function (float $seconds): void {
            $this->sleeps[] = $seconds;
        };
        return $ws;
    }

    public function testReplaysQueuedSubscriptionsAndDecodesMessages(): void
    {
        $socket = new FakeSocket([
            self::CONNECTED,
            ['type' => 'brand_new', 'x' => 1],
            ['type' => 'lagged', 'missed' => 120],
            ['type' => 'orderbook', 'code' => 'BBCA', 'side' => 'bid', 'levels' => [[9875, 120]], 'seq' => null],
            ['type' => 'stock', 'code' => 'BBCA', 'last' => 5650.0, 'prev' => 5550.0, 'open' => null, 'change_pct' => 1.8,
                'volume' => 114428700, 'iev' => 7927800, 'time' => '110436', 'seq' => 4408664],
            ['type' => 'index', 'code' => 'Investor33', 'last' => 5221.553, 'change_pct' => 1.462,
                'volume' => 20865566335, 'time' => '110512'],
            ['type' => 'trades', 'ts' => 1793240653184, 'data' => [
                ['id' => '202610290000255958', 'time' => '092413', 'code' => 'ISAT', 'price' => 1465.0, 'lot' => 9,
                    'value' => 1318500, 'change' => -105.0, 'side' => 'buy'],
            ]],
        ]);
        $ws = $this->realtime([$socket]);
        $ws->subscribeTrades([' bbca', 'tlkm', 'BBCA'], minLot: 500);
        $ws->subscribeOrderbook(['bbca']);
        $ws->subscribeStock(['bbca']);
        $ws->subscribeIndex(['composite', 'LQ45']);
        $ws->unsubscribe('index', ['lq45']);

        $types = [];
        $orderbook = null;
        $stock = null;
        $index = null;
        $trades = null;
        $lagged = null;
        $closed = null;
        /** @param array<string, mixed> $m */
        $onMessage = function (array $m) use (&$types): void {
            $types[] = $m['type'];
        };
        $ws->onMessage($onMessage)
            ->onLagged(function (int $missed) use (&$lagged): void {
                $lagged = $missed;
            })
            ->onOrderbook(function (OrderbookMessage $m) use (&$orderbook): void {
                $orderbook = $m;
            })
            ->onStock(function (StockMessage $m) use (&$stock): void {
                $stock = $m;
            })
            ->onIndex(function (IndexMessage $m) use (&$index): void {
                $index = $m;
            })
            ->onTrades(function (TradesMessage $m) use (&$trades, $ws): void {
                $trades = $m;
                $ws->close();
            })
            ->onClose(function (int $code) use (&$closed): void {
                $closed = $code;
            });
        $ws->run();

        self::assertSame([
            ['op' => 'subscribe', 'channel' => 'trades', 'codes' => ['BBCA', 'TLKM'], 'min_lot' => 500],
            ['op' => 'subscribe', 'channel' => 'orderbook', 'codes' => ['BBCA']],
            ['op' => 'subscribe', 'channel' => 'stock', 'codes' => ['BBCA']],
            ['op' => 'subscribe', 'channel' => 'index', 'codes' => ['COMPOSITE']],
        ], $socket->sent);
        self::assertSame(['connected', 'brand_new', 'lagged', 'orderbook', 'stock', 'index', 'trades'], $types);
        self::assertEquals(new StockMessage(
            'BBCA', 5650.0, 5550.0, null, null, null, null, 1.8, 114428700, null, null, null, null, 7927800, '110436', 4408664,
        ), $stock);
        self::assertEquals(new IndexMessage(
            'Investor33', 5221.553, null, null, null, null, null, 1.462, 20865566335, null, null, '110512',
        ), $index);
        self::assertSame(120, $lagged);
        self::assertEquals(new OrderbookMessage('BBCA', 'bid', [[9875, 120]], null), $orderbook);
        self::assertEquals(new TradesMessage(1793240653184, [
            new Trade('202610290000255958', '092413', 'ISAT', 1465.0, 9, 1318500, -105.0, 'buy'),
        ]), $trades);
        self::assertTrue($socket->closed);
        self::assertSame(1000, $closed);
        self::assertSame([], $this->sleeps);

        [$url, $headers] = $this->connects[0];
        self::assertSame('wss://api.invezgo.com/ws', $url);
        self::assertSame('Bearer KEY_A', $headers['Authorization']);
        self::assertSame('invezgo-php-sdk/' . InvezgoClient::VERSION, $headers['User-Agent']);
    }

    public function testSendsImmediatelyOnceConnected(): void
    {
        $socket = new FakeSocket([self::CONNECTED, ['type' => 'pong']]);
        $ws = $this->realtime([$socket]);
        $ws->onConnected(function () use ($ws): void {
            $ws->subscribeTrades(minValue: 100000000);
            $ws->unsubscribe('trades', ['tlkm']);
            $ws->ping();
        })->onPong(fn () => $ws->close());
        $ws->run();

        self::assertSame([
            ['op' => 'subscribe', 'channel' => 'trades', 'min_value' => 100000000],
            ['op' => 'unsubscribe', 'channel' => 'trades', 'codes' => ['TLKM']],
            ['op' => 'ping'],
        ], $socket->sent);
    }

    public function testReconnectsWithBackoffAndReplaysSubscriptions(): void
    {
        $fail = fn (): ClientException => new ClientException('refused');
        $first = new FakeSocket([self::CONNECTED]); // then drops
        $second = new FakeSocket([self::CONNECTED]);
        $ws = $this->realtime([$fail(), $fail(), $fail(), $fail(), $fail(), $fail(), $first, $fail(), $second]);
        $ws->subscribeOrderbook(['BBCA']);
        $ws->unsubscribe('orderbook', ['TLKM']);

        $reconnects = [];
        $closes = [];
        $ws->onReconnect(function (int $attempts) use (&$reconnects, $ws): void {
            $reconnects[] = $attempts;
            $ws->close();
        })->onClose(function (int $code) use (&$closes): void {
            $closes[] = $code;
        });
        $ws->run();

        self::assertSame([1.0, 2.0, 4.0, 8.0, 16.0, 30.0, 1.0, 2.0], $this->sleeps);
        self::assertSame([2], $reconnects);
        self::assertSame([1006, 1000], $closes);
        $replay = [['op' => 'subscribe', 'channel' => 'orderbook', 'codes' => ['BBCA']]];
        self::assertSame($replay, $first->sent);
        self::assertSame($replay, $second->sent);
    }

    public function testCloseCode4400StopsReconnecting(): void
    {
        $socket = new FakeSocket([self::CONNECTED, ['type' => 'error', 'message' => 'invalid code'], new Close(4400, 'invalid')]);
        $ws = $this->realtime([$socket]);
        $error = null;
        $close = null;
        $ws->onError(function (string $message) use (&$error): void {
            $error = $message;
        })->onClose(function (int $code, string $reason) use (&$close): void {
            $close = [$code, $reason];
        });
        $ws->run();

        self::assertSame('invalid code', $error);
        self::assertSame([4400, 'invalid'], $close);
        self::assertCount(1, $this->connects);
        self::assertSame([], $this->sleeps);
    }

    public function testHandshake401And403AreFatal(): void
    {
        $ws = $this->realtime([new HandshakeException('denied', new Response(401))]);
        try {
            $ws->run();
            self::fail('Expected AuthenticationException');
        } catch (AuthenticationException $e) {
            self::assertSame(401, $e->status);
        }

        $ws = $this->realtime([new HandshakeException('denied', new Response(403))]);
        try {
            $ws->run();
            self::fail('Expected PaymentRequiredException');
        } catch (PaymentRequiredException $e) {
            self::assertSame(403, $e->status);
        }
        self::assertSame([], $this->sleeps);
    }

    public function testHandshake429Reconnects(): void
    {
        $socket = new FakeSocket([self::CONNECTED]);
        $ws = $this->realtime([new HandshakeException('busy', new Response(429)), $socket]);
        $ws->onConnected(fn () => $ws->close());
        $ws->run();

        self::assertSame([1.0], $this->sleeps);
    }

    public function testNoReconnectWhenDisabled(): void
    {
        $ws = $this->realtime([new FakeSocket([self::CONNECTED])], reconnect: false);
        $this->expectException(ConnectionException::class);
        $ws->run();
    }

    public function testClientSideGuards(): void
    {
        $ws = new RealtimeClient('KEY_A');
        /** @return list<string> */
        $codes = static fn (int $n): array => array_map(static fn (int $i): string => 'C' . $i, range(1, $n));

        $ws->subscribeOrderbook($codes(20));
        $ws->subscribeTrades($codes(200));
        $ws->subscribeStock($codes(50));
        $ws->subscribeIndex($codes(100));
        foreach ([
            fn () => $ws->subscribeStock([]),
            fn () => $ws->subscribeStock(['NEW']),
            fn () => $ws->subscribeIndex(['NEW']),
            fn () => $ws->subscribeIndex([]),
            fn () => $ws->subscribeOrderbook([]),
            fn () => $ws->subscribeOrderbook(['NEW']),
            fn () => $ws->subscribeTrades(['NEW']),
            fn () => $ws->subscribeTrades([]),
            fn () => $ws->subscribeTrades(['  ']),
            fn () => $ws->unsubscribe('quotes'),
            fn () => new RealtimeClient(''),
            fn () => new RealtimeClient('KEY_A', 'https://api.invezgo.com/ws'),
        ] as $i => $call) {
            try {
                $call();
                self::fail("Guard {$i} did not throw");
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testClientDerivesWebSocketUrlFromBaseUrl(): void
    {
        self::assertSame('wss://api.invezgo.com/ws', (new InvezgoClient('KEY_A'))->realtime()->url);
        self::assertSame('ws://localhost:8080/ws', (new InvezgoClient('KEY_A', baseUrl: 'http://localhost:8080/'))->realtime()->url);
    }
}
