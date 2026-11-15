<?php

declare(strict_types=1);

namespace Invezgo\Exception;

/**
 * A webhook request failed verification. Answer it with HTTP 400/401 and do not process it.
 *
 * Not an {@see ApiException}: no HTTP call to the Invezgo API was made.
 */
class WebhookVerificationException extends InvezgoException
{
    public const REASON_TIMESTAMP = 'timestamp';
    public const REASON_SIGNATURE = 'signature';
    public const REASON_PAYLOAD = 'payload';

    /**
     * @param 'timestamp'|'signature'|'payload' $reason Which check failed.
     */
    public function __construct(
        string $message,
        public readonly string $reason,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
