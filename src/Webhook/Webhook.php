<?php

declare(strict_types=1);

namespace Invezgo\Webhook;

use Invezgo\Exception\WebhookVerificationException;

/**
 * Verify and parse Invezgo webhook deliveries (HMAC-SHA256). Standalone: no client or API key needed.
 *
 * ```php
 * $event = Webhook::verify(file_get_contents('php://input'), getallheaders(), $secret);
 * ```
 */
final class Webhook
{
    public const DEFAULT_TOLERANCE = 300;

    /**
     * Verify the signature and timestamp of a delivery, then decode it.
     *
     * @param string $rawBody  The exact request body as received (never re-serialised JSON).
     * @param array<string, string|list<string>> $headers Request headers; names are matched case-insensitively,
     *                         values may be strings or lists (PSR-7 `getHeaders()`, Laravel `headers->all()`).
     * @param string $secret   Webhook secret from https://invezgo.com/id/setting/api.
     * @param int    $tolerance Maximum allowed |now - X-Invezgo-Timestamp|, in seconds.
     * @param int|null $now    Current Unix time in seconds (defaults to `time()`).
     *
     * @throws WebhookVerificationException `reason` is "timestamp", "signature" or "payload".
     */
    public static function verify(
        string $rawBody,
        array $headers,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE,
        ?int $now = null,
    ): WebhookEvent {
        $timestamp = self::header($headers, 'X-Invezgo-Timestamp');
        $signature = self::header($headers, 'X-Invezgo-Signature');

        if (preg_match('/^\d+$/', $timestamp) !== 1 || abs(($now ?? time()) - (int) $timestamp) > $tolerance) {
            throw new WebhookVerificationException(
                'Missing, malformed or expired X-Invezgo-Timestamp header.',
                WebhookVerificationException::REASON_TIMESTAMP,
            );
        }
        $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        if (!hash_equals($expected, $signature)) {
            throw new WebhookVerificationException(
                'X-Invezgo-Signature does not match the request body.',
                WebhookVerificationException::REASON_SIGNATURE,
            );
        }
        return self::parse($rawBody);
    }

    /**
     * Decode a delivery without verifying it (for tests or bodies that were already verified).
     *
     * @throws WebhookVerificationException With reason "payload" when the body is not a JSON object.
     */
    public static function parse(string $rawBody): WebhookEvent
    {
        try {
            $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new WebhookVerificationException(
                'Webhook body is not valid JSON.',
                WebhookVerificationException::REASON_PAYLOAD,
                $e,
            );
        }
        if (!is_array($data) || array_is_list($data) && $data !== []) {
            throw new WebhookVerificationException(
                'Webhook body is not a JSON object.',
                WebhookVerificationException::REASON_PAYLOAD,
            );
        }
        /** @var array<string, mixed> $data */
        return WebhookEvent::fromArray($data);
    }

    /**
     * @param array<string, string|list<string>> $headers
     */
    private static function header(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                $value = is_array($value) ? ($value[0] ?? '') : $value;
                return trim((string) $value);
            }
        }
        return '';
    }
}
