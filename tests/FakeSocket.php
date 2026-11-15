<?php

declare(strict_types=1);

namespace Invezgo\Tests;

use WebSocket\Client;
use WebSocket\Exception\ConnectionClosedException;
use WebSocket\Message\Message;
use WebSocket\Message\Text;

/**
 * In-memory stand-in for a connected phrity WebSocket client: replays scripted server frames, records sent text.
 * When the script runs out the connection "drops" (ConnectionClosedException).
 */
final class FakeSocket extends Client
{
    /** @var list<array<string, mixed>> Decoded text frames sent by the SDK. */
    public array $sent = [];

    public bool $closed = false;

    /** @var list<Message|\Throwable> */
    private array $script;

    /**
     * @param list<Message|\Throwable|array<string, mixed>> $script Server frames; arrays are sent as JSON text.
     */
    public function __construct(array $script)
    {
        parent::__construct('ws://fake.test/ws');
        $this->script = [];
        foreach ($script as $frame) {
            $this->script[] = is_array($frame) ? new Text(json_encode($frame, JSON_THROW_ON_ERROR)) : $frame;
        }
    }

    /**
     * @template T of Message
     * @param T $message
     * @return T
     */
    public function send(Message $message): Message
    {
        if ($message instanceof Text) {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($message->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $this->sent[] = $decoded;
        } else {
            $this->closed = true;
        }
        return $message;
    }

    public function receive(): Message
    {
        $next = array_shift($this->script) ?? new ConnectionClosedException();
        if ($next instanceof \Throwable) {
            throw $next;
        }
        return $next;
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function disconnect(): void
    {
    }
}
