<?php

declare(strict_types=1);

namespace Invezgo\Exception;

/**
 * The API answered with a non-2xx status.
 *
 * Subclasses narrow the status: {@see ValidationException} (400/422), {@see AuthenticationException} (401),
 * {@see PaymentRequiredException} (402), {@see NotFoundException} (404), {@see RateLimitException} (429)
 * and {@see ServerException} (5xx). `getCode()` returns the same value as `$status`.
 */
class ApiException extends InvezgoException
{
    /**
     * @param string      $message   Human readable message (array messages are joined with "; ").
     * @param int         $status    HTTP status code.
     * @param string|null $error     HTTP status text from the error body, e.g. "Unauthorized".
     * @param string|null $requestId Request ID to quote to Invezgo support.
     * @param string|null $path      Request path reported by the API.
     * @param mixed       $body      Decoded JSON error body (or the raw text when it is not JSON).
     */
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly ?string $error = null,
        public readonly ?string $requestId = null,
        public readonly ?string $path = null,
        public readonly mixed $body = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }
}
