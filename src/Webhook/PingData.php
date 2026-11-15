<?php

declare(strict_types=1);

namespace Invezgo\Webhook;

use Invezgo\Internal\Hydrate;

/**
 * `data` of `test.ping`, sent by the test button on the Webhook card.
 */
final class PingData
{
    public function __construct(
        public readonly string $message,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(message: Hydrate::string($data['message'] ?? null) ?? '');
    }
}
