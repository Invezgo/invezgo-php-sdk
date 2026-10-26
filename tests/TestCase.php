<?php

declare(strict_types=1);

namespace Invezgo\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Invezgo\Cache\CacheConfig;
use Invezgo\Http\Transport;
use Invezgo\InvezgoClient;
use Psr\Http\Message\RequestInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var list<RequestInterface> Requests sent through the mock, in order. */
    protected array $history = [];

    protected MockHandler $mock;

    protected float $now = 1000.0;

    /**
     * @param list<string> $additionalKeys
     */
    protected function client(array $additionalKeys = [], bool|CacheConfig $cache = true): InvezgoClient
    {
        $this->history = [];
        $this->mock = new MockHandler();
        $stack = HandlerStack::create($this->mock);
        $stack->push(Middleware::tap(function (RequestInterface $request): void {
            $this->history[] = $request;
        }));
        $client = new InvezgoClient('KEY_A', $additionalKeys, cache: $cache, httpClient: new Client(['handler' => $stack]));
        $transport = (fn (): Transport => $this->transport)->call($client);
        $transport->clock = fn (): float => $this->now;
        return $client;
    }

    /**
     * @param array<string, string> $headers
     */
    protected function queue(int $status, mixed $json, array $headers = []): void
    {
        $body = is_string($json) ? $json : json_encode($json, JSON_THROW_ON_ERROR);
        $this->mock->append(new Response($status, $headers + ['Content-Type' => 'application/json'], $body));
    }

    protected function request(int $i): RequestInterface
    {
        return $this->history[$i];
    }

    protected function keyOf(int $i): string
    {
        return substr($this->request($i)->getHeaderLine('Authorization'), strlen('Bearer '));
    }
}
