<?php

/**
 * Basic usage of the Invezgo PHP SDK.
 *
 * Run: INVEZGO_API_KEY=xxx [INVEZGO_API_KEY_2=yyy] php examples/basic-usage.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Invezgo\Cache\CacheConfig;
use Invezgo\Enum\FilterColumn;
use Invezgo\Enum\FilterOperator;
use Invezgo\Exception\InvezgoException;
use Invezgo\Exception\RateLimitException;
use Invezgo\InvezgoClient;

$client = new InvezgoClient(
    (string) getenv('INVEZGO_API_KEY'),
    additionalApiKeys: array_filter([(string) getenv('INVEZGO_API_KEY_2')]),
    cache: new CacheConfig(ttl: 120, realtimeTtl: 5),
);

try {
    // Quota check (never cached, does not consume quota)
    $usage = $client->usage->getApiUsage();
    echo "Quota: {$usage->remaining}/{$usage->limit} ({$usage->role})\n\n";

    // Typed list of models
    $stocks = $client->analysis->getStockList();
    echo 'Listed stocks: ' . count($stocks) . "\n";

    // Second call is served from the in-memory LRU cache (no HTTP request)
    $client->analysis->getStockList();

    // Company profile
    $info = $client->analysis->getInformation('BBCA');
    echo "{$info->code}: {$info->name}\n\n";

    // Top gainers with a filter (enums or plain strings)
    $date = new DateTimeImmutable('-1 weekday');
    $top = $client->analysis->getTopChange(
        date: $date,
        filterColumn: FilterColumn::Change,
        filterOperator: FilterOperator::Gt,
        filterValue: 5,
    );
    echo "Gainers > 5% on {$date->format('Y-m-d')}:\n";
    foreach (array_slice($top->gain, 0, 5) as $row) {
        printf("  %-6s %8.2f%%  Rp%s\n", $row->code, $row->change, number_format((float) $row->price));
    }

    // Daily candles
    $candles = $client->analysis->getAdvanceChart('BBCA', from: $date->modify('-1 month'), to: $date);
    $last = end($candles);
    if ($last !== false) {
        echo "\nBBCA last close: {$last->close} on {$last->date}\n";
    }

    // Models serialize back to the API's JSON format
    echo "\n" . json_encode($info->notation, JSON_PRETTY_PRINT) . "\n";
} catch (RateLimitException $e) {
    $kind = $e->quotaExceeded ? 'monthly quota exhausted' : 'per-minute rate limit';
    fwrite(STDERR, "All keys limited ({$kind}); retry in {$e->retryAfter}s\n");
} catch (InvezgoException $e) {
    fwrite(STDERR, get_class($e) . ': ' . $e->getMessage() . "\n");
}
