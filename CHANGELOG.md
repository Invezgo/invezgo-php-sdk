# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2026-11-11

Regenerated from the latest OpenAPI spec (Invezgo API 1.2, 109 endpoints).

### Added
- `$client->analysis->getFlowOfFunds($code, ...)`: Flow of Funds (revenue → profit flow per period, with nodes, links and bridge) for `/analysis/flow-of-funds/{code}`.
- `$client->analysis->getFreeFloat($code, ...)`: free float composition over time for `/analysis/free-float/{code}`.
- `type` filter on `$client->analysis->getDisclosure` (30 disclosure categories, enum `Invezgo\Enum\DisclosureType`).
- `id` field on disclosure items; `foreign_*` breakdown fields on shareholder classification and classify-table responses.

### Changed
- Screener and alert `formula` now accepts up to 2048 characters.
- Order book `time` accepts `HH:mm:ss` as well as `HH:mm`.
- Usage logs `limit` maximum raised to 520000.

## [2.0.0] - 2026-10-27

Complete rewrite generated from the official Invezgo OpenAPI specification (API 1.2). **Breaking release** —
see [Migrating from 1.x](#migrating-from-1x).

### Added
- All 107 official endpoints, grouped as `$client->analysis`, `batch`, `search`, `screener`, `usage`,
  `watchlists`, `journals`, `portfolios`, `alerts`, `posts`, `trades`.
- Fully typed, immutable response models (`Invezgo\Model\*`, `public readonly`, PHPDoc generics, `fromArray()`,
  `toArray()`, `JsonSerializable`), typed request models and backed enums (`Invezgo\Enum\*`, strings also accepted).
- In-memory LRU cache, enabled by default: GET responses keyed by canonical path + query, `ttl` 60 s,
  `realtimeTtl` 5 s for realtime endpoints, bounded by `maxEntries` (1000) and `maxBytes` (32 MiB); successful
  mutations purge personal data; `clearCache()`.
- `additionalApiKeys`: automatic rotation to the next key on HTTP 429 (per-endpoint minute limit or monthly quota),
  honouring `Retry-After` / `X-RateLimit-Reset`.
- Exceptions per status: `ValidationException`, `AuthenticationException`, `PaymentRequiredException`,
  `NotFoundException`, `RateLimitException` (`retryAfter`, `limit`, `remaining`, `reset`, `quotaExceeded`),
  `ServerException`, `ConnectionException`, all extending `InvezgoException`.
- Generated reference `docs/API.md`, PHPUnit test suite, PHPStan level 8.

### Changed
- Requires PHP 8.1+ and Guzzle 7.
- Resources are properties (`$client->analysis`) instead of methods (`$client->analysis()`).
- Method names follow the API reference (e.g. `information()` → `getInformation()`), query parameters are
  named arguments, responses are models instead of arrays.

### Removed
- `AiService`, `ProfileService`, `MembershipService`, `RecommendationService`, `HealthService` and journal file
  extraction (not part of the official API).

### Migrating from 1.x

| 1.x | 2.0 |
|---|---|
| PHP 7.4+ | PHP 8.1+ |
| `$client->analysis()->getStockList()` | `$client->analysis->getStockList()` (resources are properties) |
| Responses are arrays | Responses are typed models; call `toArray()` for an array |
| `information('BBCA')`, `topChange($date)`, ... | `getInformation('BBCA')`, `getTopChange(date: $date)`; names follow [docs/API.md](docs/API.md) |
| Positional query parameters | Named arguments, e.g. `from: '2024-12-01'` |
| `new InvezgoClient($key, $baseUrl)` | `new InvezgoClient($key, baseUrl: $baseUrl)` |
| No cache or key rotation | LRU cache enabled by default, `additionalApiKeys` |
| `AiService`, `ProfileService`, `MembershipService`, `RecommendationService`, `HealthService` | Removed (not part of the official API) |

Personal resource methods: `watchlists->list/add/update/updateNote/delete/listGroups/addGroup/updateGroup/deleteGroup`,
`journals->list/add/delete/getSummary`, `portfolios->list/getSummary`, `trades->list/delete/getSummary/getSummaryChart/updateNote`,
`alerts->list/create/update/delete/test`, `screener->screen/list/save/update/delete`.

## [1.0.0] - 2024-12-30

### Added
- Initial release of Invezgo PHP SDK
- Full API coverage for all endpoints
- AnalysisService with all stock analysis endpoints
- WatchlistsService for personal watchlist management
- JournalsService for journal transaction management
- PortfoliosService for portfolio management
- AiService for AI-powered analysis
- SearchService for searching stocks and users
- ProfileService for user profile operations
- MembershipService for subscription management
- PostsService for posts and content
- RecommendationService for user recommendations
- TradesService for realized trades management
- ScreenerService for stock screening
- HealthService for API health checks
- Comprehensive exception handling
- Laravel integration support
- Full documentation and examples

