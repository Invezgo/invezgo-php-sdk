<?php

declare(strict_types=1);

namespace Invezgo\Webhook;

use Invezgo\Internal\Hydrate;

/**
 * `data` of every `update.*` event: which API endpoints have fresh data.
 */
final class UpdateData
{
    /**
     * @param string       $date  "YYYY-MM-DD" ("YYYY-MM-DD HH:mm:ss" WIB for update.news).
     * @param list<string> $path  API paths whose data changed; call them to fetch the new data.
     * @param string|null  $code  Stock code, for per-issuer events.
     * @param string|null  $title Title, for news and disclosure events.
     */
    public function __construct(
        public readonly string $date,
        public readonly array $path,
        public readonly ?string $code = null,
        public readonly ?string $title = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date: Hydrate::string($data['date'] ?? null) ?? '',
            path: Hydrate::list($data['path'] ?? null, static fn (mixed $x) => Hydrate::string($x) ?? '') ?? [],
            code: Hydrate::string($data['code'] ?? null),
            title: Hydrate::string($data['title'] ?? null),
        );
    }
}
