<?php

declare(strict_types=1);

namespace Invezgo\Realtime;

use Invezgo\Exception\ApiException;
use Invezgo\Exception\AuthenticationException;
use Invezgo\Exception\ConnectionException;
use Invezgo\Exception\InvezgoException;
use Invezgo\Exception\PaymentRequiredException;
use Invezgo\Exception\RateLimitException;
use Invezgo\Exception\ServerException;
use Invezgo\Internal\Hydrate;
use Invezgo\InvezgoClient;
use WebSocket\Client;
use WebSocket\Exception\Exception as WebSocketException;
use WebSocket\Exception\HandshakeException;
use WebSocket\Message\Close;
use WebSocket\Message\Text;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

/**
 * Real-time running trades, order book, stock quotes and indices over `wss://api.invezgo.com/ws` (Elite plan, beta).
 *
 * Requires the optional package `phrity/websocket` (^3.0). `run()` blocks and dispatches messages to the
 * registered callbacks until `close()` is called (typically from inside a callback) or a fatal error occurs.
 *
 * ```php
 * $ws = $client->realtime();
 * $ws->onTrades(function (TradesMessage $m) { foreach ($m->data as $t) echo $t->code, ' ', $t->price, PHP_EOL; });
 * $ws->subscribeTrades(['BBCA', 'TLKM']);
 * $ws->run();
 * ```
 *
 * Subscriptions are remembered and (re)sent after every `connected` message, so calls made before `run()` or
 * during a reconnect are not lost. Lost connections are retried with exponential backoff (1, 2, 4, 8, 16, 30 s),
 * except after HTTP 401/403 on the handshake, close code 4400 (invalid client message) or `close()`.
 */
final class RealtimeClient
{
    public const DEFAULT_URL = 'wss://api.invezgo.com/ws';

    public const MAX_TRADE_CODES = 200;

    public const MAX_ORDERBOOK_CODES = 20;

    public const MAX_STOCK_CODES = 50;

    public const MAX_INDEX_CODES = 100;

    /**
     * Opens and handshakes a socket; replaceable in tests.
     *
     * @var \Closure(string, array<string, string>): Client
     */
    public \Closure $connector;

    /**
     * Waits between reconnect attempts; replaceable in tests.
     *
     * @var \Closure(float): void
     */
    public \Closure $sleep;

    /** @var list<string>|'*'|null Desired trades subscription: codes, every stock, or none. */
    private array|string|null $tradeCodes = null;

    private ?int $minLot = null;

    private ?int $minValue = null;

    /** @var list<string> */
    private array $orderbookCodes = [];

    /** @var list<string> */
    private array $stockCodes = [];

    /** @var list<string>|'*'|null Desired index subscription: codes, every index, or none. */
    private array|string|null $indexCodes = null;

    /** @var array<string, callable> */
    private array $handlers = [];

    private ?Client $socket = null;

    /** True between the server's `connected` message and the end of that connection. */
    private bool $ready = false;

    private bool $closing = false;

    /** Reconnect attempts since the last `connected`. */
    private int $attempt = 0;

    private bool $connectedBefore = false;

    /**
     * @param string $apiKey    API key of an account with an active Elite plan.
     * @param string $url       WebSocket URL (ws:// or wss://).
     * @param bool   $reconnect Reconnect automatically after a lost connection.
     *
     * @throws \InvalidArgumentException When the API key is empty or the URL is not ws:// / wss://.
     * @throws InvezgoException          When phrity/websocket is not installed.
     */
    public function __construct(
        private readonly string $apiKey,
        public readonly string $url = self::DEFAULT_URL,
        private readonly bool $reconnect = true,
    ) {
        if (trim($apiKey) === '') {
            throw new \InvalidArgumentException('An Invezgo API key is required.');
        }
        if (preg_match('#^wss?://#i', $url) !== 1) {
            throw new \InvalidArgumentException('The WebSocket URL must start with ws:// or wss://.');
        }
        if (!class_exists(Client::class)) {
            throw new InvezgoException(
                'The Invezgo WebSocket client needs phrity/websocket. Install it with: composer require phrity/websocket:^3.0',
            );
        }
        /** @param array<string, string> $headers */
        $this->connector = static function (string $url, array $headers): Client {
            $client = new Client($url);
            $client->addMiddleware(new CloseHandler())->addMiddleware(new PingResponder());
            foreach ($headers as $name => $value) {
                $client->addHeader($name, $value);
            }
            $client->connect();
            return $client;
        };
        $this->sleep = static function (float $seconds): void {
            usleep((int) ($seconds * 1_000_000));
        };
    }

    /**
     * Subscribe to running trades.
     *
     * @param list<string>|null $codes    Stock codes (merged with earlier ones), or null for every stock.
     * @param int|null          $minLot   Only trades of at least this many lots; 0 clears, null keeps the current filter.
     * @param int|null          $minValue Only trades worth at least this many Rupiah; 0 clears, null keeps.
     *
     * @throws \InvalidArgumentException On an empty code list, empty code or more than 200 codes.
     */
    public function subscribeTrades(?array $codes = null, ?int $minLot = null, ?int $minValue = null): void
    {
        if ($codes === null) {
            $this->tradeCodes = '*';
        } else {
            $codes = self::codes($codes);
            if ($this->tradeCodes !== '*') {
                $merged = array_values(array_unique([...($this->tradeCodes ?? []), ...$codes]));
                if (count($merged) > self::MAX_TRADE_CODES) {
                    throw new \InvalidArgumentException('At most ' . self::MAX_TRADE_CODES . ' trade codes per connection.');
                }
                $this->tradeCodes = $merged;
            }
        }
        $this->minLot = $minLot ?? $this->minLot;
        $this->minValue = $minValue ?? $this->minValue;
        $this->send(self::tradesMessage($codes, $minLot, $minValue));
    }

    /**
     * Subscribe to order book updates (merged with earlier codes).
     *
     * @param list<string> $codes
     *
     * @throws \InvalidArgumentException On an empty code list, empty code or more than 20 codes.
     */
    public function subscribeOrderbook(array $codes): void
    {
        $codes = self::codes($codes);
        $merged = array_values(array_unique([...$this->orderbookCodes, ...$codes]));
        if (count($merged) > self::MAX_ORDERBOOK_CODES) {
            throw new \InvalidArgumentException('At most ' . self::MAX_ORDERBOOK_CODES . ' order book codes per connection.');
        }
        $this->orderbookCodes = $merged;
        $this->send(['op' => 'subscribe', 'channel' => 'orderbook', 'codes' => $codes]);
    }

    /**
     * Subscribe to stock quotes (OHLC, volume, value, freq, IEP/IEV), merged with earlier codes.
     *
     * @param list<string> $codes
     *
     * @throws \InvalidArgumentException On an empty code list, empty code or more than 50 codes.
     */
    public function subscribeStock(array $codes): void
    {
        $codes = self::codes($codes);
        $merged = array_values(array_unique([...$this->stockCodes, ...$codes]));
        if (count($merged) > self::MAX_STOCK_CODES) {
            throw new \InvalidArgumentException('At most ' . self::MAX_STOCK_CODES . ' stock quote codes per connection.');
        }
        $this->stockCodes = $merged;
        $this->send(['op' => 'subscribe', 'channel' => 'stock', 'codes' => $codes]);
    }

    /**
     * Subscribe to index quotes.
     *
     * @param list<string>|null $codes Index codes such as COMPOSITE or LQ45 (merged with earlier ones), or null
     *                                 for every index.
     *
     * @throws \InvalidArgumentException On an empty code list, empty code or more than 100 codes.
     */
    public function subscribeIndex(?array $codes = null): void
    {
        $message = ['op' => 'subscribe', 'channel' => 'index'];
        if ($codes === null) {
            $this->indexCodes = '*';
        } else {
            $codes = self::codes($codes);
            if ($this->indexCodes !== '*') {
                $merged = array_values(array_unique([...($this->indexCodes ?? []), ...$codes]));
                if (count($merged) > self::MAX_INDEX_CODES) {
                    throw new \InvalidArgumentException('At most ' . self::MAX_INDEX_CODES . ' index codes per connection.');
                }
                $this->indexCodes = $merged;
            }
            $message['codes'] = $codes;
        }
        $this->send($message);
    }

    /**
     * Stop a subscription. Without codes the whole channel is dropped (for trades also both size filters).
     *
     * @param string            $channel "trades", "orderbook", "stock" or "index".
     * @param list<string>|null $codes
     *
     * @throws \InvalidArgumentException On an unknown channel or an empty code list.
     */
    public function unsubscribe(string $channel, ?array $codes = null): void
    {
        if (!in_array($channel, ['trades', 'orderbook', 'stock', 'index'], true)) {
            throw new \InvalidArgumentException(
                "Unknown channel \"{$channel}\"; use \"trades\", \"orderbook\", \"stock\" or \"index\".",
            );
        }
        $message = ['op' => 'unsubscribe', 'channel' => $channel];
        if ($codes !== null) {
            $codes = self::codes($codes);
            $message['codes'] = $codes;
        }
        if ($channel === 'orderbook') {
            $this->orderbookCodes = $codes === null ? [] : array_values(array_diff($this->orderbookCodes, $codes));
        } elseif ($channel === 'stock') {
            $this->stockCodes = $codes === null ? [] : array_values(array_diff($this->stockCodes, $codes));
        } elseif ($channel === 'index') {
            if ($codes === null) {
                $this->indexCodes = null;
            } elseif (is_array($this->indexCodes)) {
                $left = array_values(array_diff($this->indexCodes, $codes));
                $this->indexCodes = $left === [] ? null : $left;
            }
        } elseif ($codes === null) {
            $this->tradeCodes = $this->minLot = $this->minValue = null;
        } elseif (is_array($this->tradeCodes)) {
            $left = array_values(array_diff($this->tradeCodes, $codes));
            $this->tradeCodes = $left === [] ? null : $left;
        }
        $this->send($message);
    }

    /** Ask the server for a `pong` (ignored while not connected). */
    public function ping(): void
    {
        $this->send(['op' => 'ping']);
    }

    /** Close the connection and make `run()` return; no reconnect follows. */
    public function close(): void
    {
        $this->closing = true;
        if ($this->socket !== null && $this->socket->isConnected()) {
            try {
                $this->socket->close(1000, 'client close');
            } catch (WebSocketException) {
                // Already gone; run() cleans up.
            }
        }
    }

    /** @param callable(): void $callback Server accepted the connection (also after each reconnect). */
    public function onConnected(callable $callback): self
    {
        return $this->on('connected', $callback);
    }

    /** @param callable(SubscribedMessage): void $callback Current subscription after a subscribe/unsubscribe. */
    public function onSubscribed(callable $callback): self
    {
        return $this->on('subscribed', $callback);
    }

    /** @param callable(TradesMessage): void $callback */
    public function onTrades(callable $callback): self
    {
        return $this->on('trades', $callback);
    }

    /** @param callable(OrderbookMessage): void $callback */
    public function onOrderbook(callable $callback): self
    {
        return $this->on('orderbook', $callback);
    }

    /** @param callable(StockMessage): void $callback */
    public function onStock(callable $callback): self
    {
        return $this->on('stock', $callback);
    }

    /** @param callable(IndexMessage): void $callback */
    public function onIndex(callable $callback): self
    {
        return $this->on('index', $callback);
    }

    /** @param callable(): void $callback Reply to {@see self::ping()}. */
    public function onPong(callable $callback): self
    {
        return $this->on('pong', $callback);
    }

    /** @param callable(int): void $callback Number of messages skipped because the client read too slowly. */
    public function onLagged(callable $callback): self
    {
        return $this->on('lagged', $callback);
    }

    /** @param callable(string): void $callback Server rejected a client message (the server then closes with 4400). */
    public function onError(callable $callback): self
    {
        return $this->on('error', $callback);
    }

    /** @param callable(array<string, mixed>): void $callback Every decoded server message, including unknown types. */
    public function onMessage(callable $callback): self
    {
        return $this->on('message', $callback);
    }

    /** @param callable(int, string): void $callback Connection ended: close code (1006 = dropped) and reason. */
    public function onClose(callable $callback): self
    {
        return $this->on('close', $callback);
    }

    /**
     * @param callable(int): void $callback Connection re-established and subscriptions replayed, with the number
     *                                      of attempts it took. Backfill missed data via REST here
     *                                      (`analysis->getStockRunningTrade()`, `analysis->getOrderBook()`).
     */
    public function onReconnect(callable $callback): self
    {
        return $this->on('reconnect', $callback);
    }

    /**
     * Connect and dispatch messages until {@see self::close()} is called.
     *
     * Returns after `close()`, after close code 4400, or (with reconnect disabled) after the server closes.
     *
     * @throws AuthenticationException  Handshake HTTP 401: missing or invalid API key.
     * @throws PaymentRequiredException Handshake HTTP 403: no active Elite plan (`$status` is 403).
     * @throws ApiException             Other handshake rejections (429, 5xx, ...) when reconnect is disabled.
     * @throws ConnectionException      Network failure when reconnect is disabled.
     */
    public function run(): void
    {
        $this->closing = false;
        $this->attempt = 0;
        $this->connectedBefore = false;
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'User-Agent' => 'invezgo-php-sdk/' . InvezgoClient::VERSION,
        ];

        while (true) {
            $opened = false;
            $error = null;
            $code = 1006;
            $reason = '';
            try {
                $this->socket = ($this->connector)($this->url, $headers);
                $opened = true;
                [$code, $reason] = $this->listen($this->socket);
            } catch (HandshakeException $e) {
                $error = self::handshakeError($e);
            } catch (WebSocketException $e) {
                $error = new ConnectionException('Invezgo WebSocket connection failed: ' . $e->getMessage(), 0, $e);
            } finally {
                $this->ready = false;
                $socket = $this->socket;
                $this->socket = null;
                try {
                    $socket?->disconnect();
                } catch (WebSocketException) {
                    // Nothing left to clean up.
                }
            }

            if ($opened) {
                $this->emit('close', $code, $reason);
            }
            if ($this->closing || $code === 4400) {
                return;
            }
            $fatal = $error instanceof AuthenticationException || ($error instanceof ApiException && $error->status === 403);
            if ($fatal || !$this->reconnect) {
                if ($error !== null) {
                    throw $error;
                }
                return;
            }
            ($this->sleep)(self::backoff($this->attempt++));
        }
    }

    /**
     * Read until the server closes or close() is called.
     *
     * @return array{int, string} Close code and reason.
     */
    private function listen(Client $socket): array
    {
        while (!$this->closing) {
            $message = $socket->receive();
            if ($message instanceof Close) {
                return [$message->getCloseStatus() ?: 1005, $message->getContent()];
            }
            if ($message instanceof Text) {
                $this->handle($message->getContent());
            }
        }
        return [1000, 'client close'];
    }

    private function handle(string $raw): void
    {
        try {
            $message = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }
        if (!is_array($message)) {
            return;
        }
        /** @var array<string, mixed> $message */
        $this->emit('message', $message);

        switch (Hydrate::string($message['type'] ?? null)) {
            case 'connected':
                $this->ready = true;
                $this->replay();
                $reconnected = $this->connectedBefore;
                $attempts = $this->attempt;
                $this->connectedBefore = true;
                $this->attempt = 0;
                $this->emit('connected');
                if ($reconnected) {
                    $this->emit('reconnect', $attempts);
                }
                break;
            case 'subscribed':
                $this->emit('subscribed', SubscribedMessage::fromArray($message));
                break;
            case 'trades':
                $this->emit('trades', TradesMessage::fromArray($message));
                break;
            case 'orderbook':
                $this->emit('orderbook', OrderbookMessage::fromArray($message));
                break;
            case 'stock':
                $this->emit('stock', StockMessage::fromArray($message));
                break;
            case 'index':
                $this->emit('index', IndexMessage::fromArray($message));
                break;
            case 'pong':
                $this->emit('pong');
                break;
            case 'lagged':
                $this->emit('lagged', Hydrate::int($message['missed'] ?? null) ?? 0);
                break;
            case 'error':
                $this->emit('error', Hydrate::string($message['message'] ?? null) ?? '');
                break;
        }
    }

    /** Send the remembered subscriptions on a fresh connection. */
    private function replay(): void
    {
        if ($this->tradeCodes !== null) {
            $codes = $this->tradeCodes === '*' ? null : $this->tradeCodes;
            $this->send(self::tradesMessage($codes, $this->minLot, $this->minValue));
        }
        if ($this->orderbookCodes !== []) {
            $this->send(['op' => 'subscribe', 'channel' => 'orderbook', 'codes' => $this->orderbookCodes]);
        }
        if ($this->stockCodes !== []) {
            $this->send(['op' => 'subscribe', 'channel' => 'stock', 'codes' => $this->stockCodes]);
        }
        if ($this->indexCodes === '*') {
            $this->send(['op' => 'subscribe', 'channel' => 'index']);
        } elseif ($this->indexCodes !== null) {
            $this->send(['op' => 'subscribe', 'channel' => 'index', 'codes' => $this->indexCodes]);
        }
    }

    /**
     * Send now when connected; otherwise the state change is replayed after the next `connected`.
     *
     * @param array<string, mixed> $message
     */
    private function send(array $message): void
    {
        if ($this->ready && $this->socket !== null) {
            $this->socket->text(json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        }
    }

    private function on(string $event, callable $callback): self
    {
        $this->handlers[$event] = $callback;
        return $this;
    }

    private function emit(string $event, mixed ...$args): void
    {
        if (isset($this->handlers[$event])) {
            ($this->handlers[$event])(...$args);
        }
    }

    /**
     * @param list<string>|null $codes
     * @return array<string, mixed>
     */
    private static function tradesMessage(?array $codes, ?int $minLot, ?int $minValue): array
    {
        $message = ['op' => 'subscribe', 'channel' => 'trades'];
        if ($codes !== null) {
            $message['codes'] = $codes;
        }
        if ($minLot !== null) {
            $message['min_lot'] = $minLot;
        }
        if ($minValue !== null) {
            $message['min_value'] = $minValue;
        }
        return $message;
    }

    /**
     * Trim, upper-case and dedupe stock or index codes.
     *
     * @param list<string> $codes
     * @return non-empty-list<string>
     */
    private static function codes(array $codes): array
    {
        $out = [];
        foreach ($codes as $code) {
            $code = strtoupper(trim($code));
            if ($code === '') {
                throw new \InvalidArgumentException('Stock codes must not be empty.');
            }
            if (!in_array($code, $out, true)) {
                $out[] = $code;
            }
        }
        if ($out === []) {
            throw new \InvalidArgumentException('Pass at least one code (or null to subscribe trades/index for everything).');
        }
        return $out;
    }

    /** Reconnect delay in seconds: 1, 2, 4, 8, 16, then 30. */
    private static function backoff(int $attempt): float
    {
        return min(30.0, 2.0 ** min($attempt, 5));
    }

    private static function handshakeError(HandshakeException $e): ApiException
    {
        $response = $e->getResponse();
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $message = "Invezgo WebSocket handshake rejected with HTTP {$status}"
            . ($response->getReasonPhrase() !== '' ? " {$response->getReasonPhrase()}" : '') . '.';
        $error = $response->getReasonPhrase() !== '' ? $response->getReasonPhrase() : null;
        return match (true) {
            $status === 401 => new AuthenticationException($message, 401, $error, body: $body, previous: $e),
            $status === 403 => new PaymentRequiredException($message, 403, $error, body: $body, previous: $e),
            $status === 429 => new RateLimitException($message, $error, body: $body),
            $status >= 500 => new ServerException($message, $status, $error, body: $body, previous: $e),
            default => new ApiException($message, $status, $error, body: $body, previous: $e),
        };
    }
}
