<?php

declare(strict_types=1);

namespace Invezgo\Tests;

use Invezgo\Exception\InvezgoException;
use Invezgo\Exception\WebhookVerificationException;
use Invezgo\Webhook\AlertStock;
use Invezgo\Webhook\AlertTriggeredData;
use Invezgo\Webhook\QuotaData;
use Invezgo\Webhook\UpdateData;
use Invezgo\Webhook\Webhook;

final class WebhookTest extends \PHPUnit\Framework\TestCase
{
    private const SECRET = 'whsec_test';
    private const NOW = 1762425300;
    private const BODY = '{"id":"6f1c2b8e","domain":"update","type":"summary","created_at":1762425300,'
        . '"data":{"date":"2026-11-06","path":["/analysis/summary/stock/BBCA"]}}';

    /**
     * @return array<string, string>
     */
    private static function headers(string $body = self::BODY, int|string $ts = self::NOW, string $secret = self::SECRET): array
    {
        return [
            'X-Invezgo-Timestamp' => (string) $ts,
            'X-Invezgo-Signature' => 'sha256=' . hash_hmac('sha256', $ts . '.' . $body, $secret),
            'X-Invezgo-Event' => 'update.summary',
        ];
    }

    private static function reason(\Closure $fn): string
    {
        try {
            $fn();
        } catch (WebhookVerificationException $e) {
            return $e->reason;
        }
        self::fail('Expected WebhookVerificationException');
    }

    public function testVerifiesValidDelivery(): void
    {
        $event = Webhook::verify(self::BODY, self::headers(), self::SECRET, now: self::NOW + 10);

        self::assertSame('6f1c2b8e', $event->id);
        self::assertSame('update.summary', $event->name());
        self::assertSame(1762425300, $event->createdAt);
        $data = $event->typedData();
        self::assertInstanceOf(UpdateData::class, $data);
        self::assertSame('2026-11-06', $data->date);
        self::assertSame(['/analysis/summary/stock/BBCA'], $data->path);
        self::assertNull($data->code);
    }

    public function testHeadersAreCaseInsensitiveAndMayBeLists(): void
    {
        $headers = [];
        foreach (self::headers() as $name => $value) {
            $headers[strtolower($name)] = [$value];
        }
        self::assertSame('6f1c2b8e', Webhook::verify(self::BODY, $headers, self::SECRET, now: self::NOW)->id);
    }

    public function testRejectsBadSignature(): void
    {
        self::assertSame('signature', self::reason(fn () => Webhook::verify(self::BODY, self::headers(secret: 'other'), self::SECRET, now: self::NOW)));
        self::assertSame('signature', self::reason(fn () => Webhook::verify(self::BODY . ' ', self::headers(), self::SECRET, now: self::NOW)));
        $missing = self::headers();
        unset($missing['X-Invezgo-Signature']);
        self::assertSame('signature', self::reason(fn () => Webhook::verify(self::BODY, $missing, self::SECRET, now: self::NOW)));
    }

    public function testRejectsStaleFutureAndMalformedTimestamps(): void
    {
        self::assertSame('timestamp', self::reason(fn () => Webhook::verify(self::BODY, self::headers(), self::SECRET, now: self::NOW + 301)));
        self::assertSame('timestamp', self::reason(fn () => Webhook::verify(self::BODY, self::headers(), self::SECRET, now: self::NOW - 301)));
        self::assertSame('timestamp', self::reason(fn () => Webhook::verify(self::BODY, self::headers(ts: '17624253x0'), self::SECRET, now: self::NOW)));
        self::assertSame('timestamp', self::reason(fn () => Webhook::verify(self::BODY, self::headers(ts: '-5'), self::SECRET, now: self::NOW)));
        self::assertSame('timestamp', self::reason(fn () => Webhook::verify(self::BODY, [], self::SECRET, now: self::NOW)));
        // Exactly at the tolerance is still accepted; a custom tolerance is honoured.
        self::assertSame('6f1c2b8e', Webhook::verify(self::BODY, self::headers(), self::SECRET, now: self::NOW + 300)->id);
        self::assertSame('timestamp', self::reason(fn () => Webhook::verify(self::BODY, self::headers(), self::SECRET, 10, self::NOW + 11)));
    }

    public function testRejectsBadJsonAfterValidSignature(): void
    {
        $body = '{"id":';
        self::assertSame('payload', self::reason(fn () => Webhook::verify($body, self::headers($body), self::SECRET, now: self::NOW)));
        self::assertSame('payload', self::reason(fn () => Webhook::parse('"text"')));
    }

    public function testExceptionIsNotAnApiException(): void
    {
        $e = new WebhookVerificationException('x', WebhookVerificationException::REASON_SIGNATURE);
        self::assertInstanceOf(InvezgoException::class, $e);
        self::assertNotInstanceOf(\Invezgo\Exception\ApiException::class, $e);
    }

    public function testParsesTypedDataForDocumentedEvents(): void
    {
        $alert = Webhook::parse('{"id":"evt_1","domain":"alert","type":"triggered","created_at":1762412400,"data":'
            . '{"alert_id":"a1","alert_name":"Breakout","stocks_in":[{"code":"BBCA","price":9875}],"stocks_out":[]}}');
        $data = $alert->typedData();
        self::assertInstanceOf(AlertTriggeredData::class, $data);
        self::assertSame('Breakout', $data->alertName);
        self::assertEquals([new AlertStock('BBCA', 9875.0)], $data->stocksIn);
        self::assertSame([], $data->stocksOut);

        $quota = Webhook::parse('{"id":"q","domain":"quota","type":"low","created_at":1,"data":'
            . '{"plan":"PRIME","limit":90000,"used":85500,"remaining":4500,"reset_at":null}}')->typedData();
        self::assertEquals(new QuotaData('PRIME', 90000, 85500, 4500, null), $quota);

        $unknown = Webhook::parse('{"id":"n","domain":"brand","type":"new","created_at":1,"data":{"x":1}}');
        self::assertSame('brand.new', $unknown->name());
        self::assertNull($unknown->typedData());
        self::assertSame(['x' => 1], $unknown->data);
    }
}
