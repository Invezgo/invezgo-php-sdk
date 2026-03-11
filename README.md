# Invezgo PHP SDK

Official PHP SDK for [Invezgo API](https://invezgo.com) - Data Saham Indonesia

[![PHP Version](https://img.shields.io/badge/php-7.4%2B-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## Installation

Install via Composer:

```bash
composer require invezgo/invezgo-php-sdk
```

## Requirements

- PHP 7.4 or higher
- Guzzle HTTP Client 6.0 or higher

## Getting Started

### Basic Usage

```php
<?php

require 'vendor/autoload.php';

use Invezgo\InvezgoClient;

// Initialize the client with your API key
$client = new InvezgoClient('your-api-key-here');

// Get list of stocks
$stocks = $client->analysis()->getStockList();
print_r($stocks);

// Get company information
$info = $client->analysis()->information('BBCA');
print_r($info);
```

### Authentication

You need an API key to use this SDK. Get your API key from [Invezgo API Settings](https://invezgo.com/id/setting/api).

**Note:** You must have an active subscription package to use the API.

## Usage Examples

### Analysis Service - Data Saham Indonesia

#### List Endpoints

```php
// Get list of all stocks
$stocks = $client->analysis()->getStockList();

// Get list of all brokers
$brokers = $client->analysis()->getBrokerList();

// Get list of all indexes
$indexes = $client->analysis()->getIndexList();
```

#### Company Information

```php
// Get company information
$info = $client->analysis()->information('BBCA');
```

#### Top Gainer & Loser

```php
// Get top gainer and loser for a specific date
$topChange = $client->analysis()->topChange('2024-12-30');
```

#### Top Foreign Flow

```php
// Get top foreign accumulation and distribution
$topForeign = $client->analysis()->topForeign('2024-12-30');
```

#### Top BDM Flow

```php
// Get top bandarmologi accumulation and distribution
$topAccumulation = $client->analysis()->topAccumulation('2024-12-30');
```

#### Top Ritel Flow

```php
// Get top retail accumulation and distribution
$topRitel = $client->analysis()->topRitel('2024-12-30');
```

#### Stock Charts

```php
// Get stock chart (OHLCV data)
$chart = $client->analysis()->getAdvanceChart('BBCA', '2024-12-01', '2024-12-30');

// Get index chart
$indexChart = $client->analysis()->getIndexChart('COMPOSITE', '2024-12-01', '2024-12-30');

// Get indicator chart (bdm, foreign, ritel, ratio)
$indicatorChart = $client->analysis()->getIndicatorChart('BBCA', 'bdm', '2024-12-01', '2024-12-30');
```

#### Intraday Data

```php
// Get intraday chart
$intraday = $client->analysis()->getIntradayChart('BBCA', 'RG');

// Get intraday data
$intradayData = $client->analysis()->getIntradayData('BBCA', 'RG');

// Get intraday index data
$intradayIndex = $client->analysis()->getIntradayIndex('COMPOSITE', 'RG');

// Get order book
$orderBook = $client->analysis()->getOrderBook('BBCA', 'RG');
```

#### Sector Analysis

```php
// Sector stalker - track sector/index movement
$sectorStalker = $client->analysis()->sectorStalker('2024-12-01', '2024-12-30', 'COMPOSITE');

// Sector rotation chart
$sectorRotation = $client->analysis()->sectorRotation('2024-12-01', '2024-12-30', 'COMPOSITE');
```

#### Broker Analysis

```php
// Broker stalker - track broker activity on specific stock
$brokerStalker = $client->analysis()->brokerStalker('AG', 'BBCA', '2024-12-01', '2024-12-30', 'all', 'RG', 'value');

// Broker stalker list - get all stocks traded by a broker
$brokerStalkerList = $client->analysis()->brokerStalkerList('AG', '2024-12-01', '2024-12-30', 'all', 'value', 'RG');

// Broker summary per stock
$summaryStock = $client->analysis()->summaryStock('BBCA', '2024-12-01', '2024-12-30', 'all', 'RG');

// Broker summary per broker
$summaryBroker = $client->analysis()->summaryBroker('AG', '2024-12-01', '2024-12-30', 'all', 'RG');

// Broker summary chart for stock
$summaryStockChart = $client->analysis()->summaryStockChart('BBCA', '2024-12-01', '2024-12-30', 'volume', 'RG');

// Broker summary chart for broker
$summaryBrokerChart = $client->analysis()->summaryBrokerChart('AG', '2024-12-01', '2024-12-30', 'volume', 'RG');
```

#### Inventory Charts

```php
// Inventory chart for stock
$inventoryStock = $client->analysis()->inventoryStockChart('BBCA', '2024-12-01', '2024-12-30', 'val', 'all', 5, 'RG');

// Inventory chart for broker
$inventoryBroker = $client->analysis()->inventoryBrokerChart('AG', '2024-12-01', '2024-12-30', 'val', 'all', 5, 'RG');
```

#### Momentum & Realtime Analysis

```php
// Momentum chart
$momentum = $client->analysis()->momentumChart('BBCA', '2024-12-30', 60, 'value');

// Intraday inventory chart
$intradayInventory = $client->analysis()->intradayInventoryChart('BBCA', '2024-12-30', 60, 'value', 10, 'ALL', 'ALL', 'RG');

// Sankey chart (crossing visualization)
$sankey = $client->analysis()->sankeyChart('BBCA', '2024-12-30', 'value', 'ALL', 'ALL', 'RG');

// Price table
$priceTable = $client->analysis()->priceTable('BBCA', '2024-12-30');

// Time table
$timeTable = $client->analysis()->timeTable('BBCA', '2024-12-30', 60);
```

#### Price History

```php
// Price diary (daily price changes)
$priceDiary = $client->analysis()->priceDiary('BBCA');

// Price seasonality (monthly price changes)
$priceSeasonality = $client->analysis()->priceSeasonality('BBCA', 12);
```

#### Shareholder Data

```php
// Shareholder composition
$shareholder = $client->analysis()->shareholder('BBCA');

// Shareholder detail (5%)
$shareholderDetail = $client->analysis()->shareholderDetail('BBCA', 'SHAREHOLDER NAME');

// Shareholder detail one (1%)
$shareholderDetailOne = $client->analysis()->shareholderDetailOne('BBCA', 'SHAREHOLDER NAME');

// Shareholder number
$shareholderNumber = $client->analysis()->shareholderNumber('BBCA');

// KSEI shareholder data
$ksei = $client->analysis()->shareholderKSEI('BBCA', 6);

// Shareholder relation graph
$shareholderRelation = $client->analysis()->shareholderRelation('BBCA', null, 3);
```

#### Insider Trading

```php
// Shareholder above 5%
$shareholderAbove = $client->analysis()->shareholderAbove('2024-12-01', '2024-12-30', 1, 10, 'BBCA');

// Shareholder above 5% chart
$shareholderAboveChart = $client->analysis()->shareholderAboveChart('BBCA', 'SHAREHOLDER NAME');

// Shareholder one (1%)
$shareholderOne = $client->analysis()->shareholderOne('2024-12-01', '2024-12-30', 1, 10, 'BBCA');

// Shareholder one chart (1%)
$shareholderOneChart = $client->analysis()->shareholderOneChart('BBCA', 'SHAREHOLDER NAME');

// Insider trading data
$insider = $client->analysis()->insider('2024-12-01', '2024-12-30', 1, 10, null, 'BBCA');

// Insider chart
$insiderChart = $client->analysis()->insiderChart('BBCA', 'SHAREHOLDER NAME');
```

#### Financial Statements

```php
// Financial statement (BS = Balance Sheet, IS = Income Statement, CF = Cash Flow)
// Period: FY (Annual), Q (Quarterly), Q1-Q4 (Specific Quarter)
$financial = $client->analysis()->financialStatement('BBCA', 'BS', 'Q', 10);

// Financial statement chart
$financialChart = $client->analysis()->financialStatementChart('BBCA', 'BS', 'Q', 'ACCOUNT_ID', 10);

// Key statistics
$keystat = $client->analysis()->keystat('BBCA', 'Q', 10);

// Key statistics chart
$keystatChart = $client->analysis()->keystatChart('BBCA', 'Q', 'ROE', 10);
```

#### Corporate Action Calendar

```php
// Get corporate action calendar
$calendar = $client->analysis()->calendar();

// Filter by stock code
$calendarByStock = $client->analysis()->calendar('BBCA');

// Filter by type (IPO, PUBLIC_EXPOSE, REVERSE, RIGHT, RUPS_RESULT, RUPS_SCHEDULE, SPLIT, WARRANT, BONUS, CONVERTION, DIVIDEND)
$calendarByType = $client->analysis()->calendar(null, 'DIVIDEND', 1, 20);
```

### Watchlists Service - Personal

```php
// Get watchlist groups
$groups = $client->watchlists()->listGroupWatchlist();

// Get watchlists by group
$watchlists = $client->watchlists()->listWatchlist('group-id');

// Add to watchlist
$result = $client->watchlists()->addWatchlist([
    'stock_code' => 'BBCA',
    'group_id' => 'group-id',
]);

// Update watchlist
$result = $client->watchlists()->updateWatchlist('watchlist-id', [
    'stock_code' => 'BBCA',
]);

// Update watchlist note
$result = $client->watchlists()->updateNoteWatchlist('watchlist-id', [
    'note' => 'My note',
]);

// Delete watchlist
$result = $client->watchlists()->deleteWatchlists(['ids' => ['id1', 'id2']]);

// Add watchlist group
$result = $client->watchlists()->addGroupWatchlist([
    'name' => 'My Watchlist',
]);
```

### Journals Service - Personal

```php
// List journal transactions
$journals = $client->journals()->listTransactions();

// Add journal transaction
$result = $client->journals()->addTransaction([
    'stock_code' => 'BBCA',
    'transaction_type' => 'buy',
    'price' => 10000,
    'quantity' => 100,
]);

// Get transactions summary
$summary = $client->journals()->getTransactionsSummary();

// Extract journal from file
$result = $client->journals()->extractInformation([
    'file' => 'file-content',
]);

// Delete journals
$result = $client->journals()->deleteWatchlists(['ids' => ['id1', 'id2']]);

// Update journal note
$result = $client->journals()->updateNoteWatchlist('journal-id', [
    'note' => 'My note',
]);
```

### Portfolios Service - Personal

```php
// List portfolios
$portfolios = $client->portfolios()->listPortfolio();

// Get portfolio summary
$summary = $client->portfolios()->portfolioSummary();
```

### Trades Service - Personal

```php
// List realized transactions
$trades = $client->trades()->listTransactions();

// Get transactions summary
$summary = $client->trades()->getTransactionsSummary();

// Get summary chart
$chart = $client->trades()->getSummaryChart();

// Delete trades
$result = $client->trades()->deleteWatchlists(['ids' => ['id1', 'id2']]);

// Update trade note
$result = $client->trades()->updateNoteWatchlist('trade-id', [
    'note' => 'My note',
]);
```

### AI Service - AI Chat

```php
// AI analysis for KSEI shareholder
$analysis = $client->ai()->shareholderKSEI('BBCA');

// AI analysis for inventory chart stock
$analysis = $client->ai()->inventoryStockChart('BBCA', '2024-12-01', '2024-12-30', 'val', 'all', '5', 'RG', '');

// AI analysis for news
$news = $client->ai()->news('BBCA');

// AI analysis for broker summary
$analysis = $client->ai()->brokerSummary('BBCA', '2024-12-01', '2024-12-30', 'all', 'RG');

// AI analysis for insider shareholder
$analysis = $client->ai()->insider('BBCA', 'NAME', '2024-12-01', '2024-12-30', '1', '10');

// AI analysis for shareholder above 5%
$analysis = $client->ai()->shareholderAbove('BBCA', 'BROKER', 'NAME', '2024-12-01', '2024-12-30', '1', '10');

// AI analysis for intraday inventory
$analysis = $client->ai()->intradayInventory('BBCA', 60, 'value', 10, 'ALL', 'ALL', '', 'RG');

// AI analysis for sankey chart
$analysis = $client->ai()->sankeyChart('BBCA', 'value', 'ALL', 'ALL', 'RG');

// AI analysis for shareholder table
$analysis = $client->ai()->shareholderTable('BBCA');

// AI analysis for financial statement
$analysis = $client->ai()->financialStatement('BBCA', 'BS', 'Q', '10');

// AI analysis for key statistics
$analysis = $client->ai()->keystat('BBCA', 'Q', '10');
```

### Search Service

```php
// Search stock or user
$results = $client->search()->search('BBCA');

// Search stock only
$stocks = $client->search()->searchStock('BCA', 'cursor');

// Search user only
$users = $client->search()->searchUser('username', 'cursor');
```

### Profile Service

```php
// Get user profile
$profile = $client->profile()->userDetails('username');

// Get user posts
$posts = $client->profile()->userPosts('username', '1', '10');

// Get user posts by category
$posts = $client->profile()->categoryPosts('username', 'category', '1', '10');

// Get user watchlist
$watchlist = $client->profile()->listWatchlist('username');

// Get user followers
$followers = $client->profile()->followUser('username');

// Get user following
$following = $client->profile()->getFollowingUser('username');

// Get user memberships
$memberships = $client->profile()->getMemberships('username');
```

### Posts Service

```php
// Get all posts
$posts = $client->posts()->getPosts();

// Get posts by category
$posts = $client->posts()->getCategoryPosts('category');

// Get stock posts
$posts = $client->posts()->getStockPosts('BBCA');

// Get stock posts by category
$posts = $client->posts()->getStockCategoryPosts('BBCA', 'category');

// Get post detail
$post = $client->posts()->getPostById('post-id');

// Get post comments
$comments = $client->posts()->getComment('post-id');

// Get liked posts
$liked = $client->posts()->getLike();

// Get favorite posts
$favorites = $client->posts()->getFavorite();

// Get post voters
$voters = $client->posts()->getVoters('post-id');
```

### Membership Service

```php
// Get list of memberships
$memberships = $client->membership()->getMemberships();

// Add new membership
$result = $client->membership()->addMembership([
    'plan_id' => 'plan-id',
]);

// Get membership scope
$scope = $client->membership()->getScope();

// Get transaction membership list
$transactions = $client->membership()->getTransactionMembership();

// Update membership
$result = $client->membership()->changeMembership('membership-id', [
    'status' => 'active',
]);

// Delete membership
$result = $client->membership()->deleteMembership('membership-id');
```

### Screener Service

```php
// Get list of screener presets
$presets = $client->screener()->list();

// Save screener preset
$result = $client->screener()->save([
    'name' => 'My Screener',
    'filters' => [],
]);

// Update screener preset
$result = $client->screener()->update('screener-id', [
    'name' => 'Updated Screener',
]);

// Delete screener preset
$result = $client->screener()->delete('screener-id');

// Run screener
$results = $client->screener()->screen([
    'formula' => 'prev < close',
]);
```

### Recommendation Service

```php
// Get user recommendations
$recommendations = $client->recommendation()->userRecommendations();
```

### Health Service

```php
// Check API status
$status = $client->health()->check();

// Check database status
$dbStatus = $client->health()->checkDatabase();

// Full check
$fullStatus = $client->health()->fullCheck();
```

### Alerts Service - Live Alert

```php
// Get list of alerts
$alerts = $client->alerts()->list();

// Create new alert
$result = $client->alerts()->create([
    'formula' => 'prev < close',
    'name' => 'My Alert',
]);

// Test alert formula
$result = $client->alerts()->test([
    'formula' => 'prev < close',
]);

// Update alert
$result = $client->alerts()->update('alert-id', [
    'formula' => 'prev > close',
]);

// Delete alert
$result = $client->alerts()->delete('alert-id');
```

## Error Handling

The SDK throws specific exceptions for different error scenarios:

```php
use Invezgo\Exception\ApiException;
use Invezgo\Exception\AuthenticationException;
use Invezgo\Exception\PaymentRequiredException;
use Invezgo\Exception\RateLimitException;

try {
    $stocks = $client->analysis()->getStockList();
} catch (AuthenticationException $e) {
    // API Key tidak valid atau tidak ditemukan (401)
    echo "Authentication failed: " . $e->getMessage();
} catch (PaymentRequiredException $e) {
    // Paket berlangganan tidak mencukupi atau API Key sudah expired (402)
    echo "Payment required: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Melebihi batas permintaan API (429)
    echo "Rate limit exceeded: " . $e->getMessage();
} catch (ApiException $e) {
    // Other API errors
    echo "API Error: " . $e->getMessage();
}
```

## Laravel Integration

### Service Provider (Optional)

Create a service provider to bind the client:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Invezgo\InvezgoClient;

class InvezgoServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(InvezgoClient::class, function ($app) {
            return new InvezgoClient(config('services.invezgo.api_key'));
        });
    }
}
```

Add to `config/services.php`:

```php
'invezgo' => [
    'api_key' => env('INVEZGO_API_KEY'),
],
```

Add to `.env`:

```
INVEZGO_API_KEY=your-api-key-here
```

### Usage in Laravel

```php
use Invezgo\InvezgoClient;

class StockController extends Controller
{
    public function index(InvezgoClient $client)
    {
        $stocks = $client->analysis()->getStockList();
        return view('stocks.index', compact('stocks'));
    }
}
```

## Available Services

- **AnalysisService** - Data Saham Indonesia (stocks, charts, financials, shareholders, brokers, sectors, etc.)
- **WatchlistsService** - Personal watchlist management
- **JournalsService** - Journal transaction management
- **PortfoliosService** - Portfolio management
- **TradesService** - Realized trades management
- **AiService** - AI-powered analysis
- **SearchService** - Search stocks and users
- **ProfileService** - User profile operations
- **MembershipService** - Membership/subscription management
- **PostsService** - Posts and content
- **RecommendationService** - User recommendations
- **ScreenerService** - Stock screener
- **AlertsService** - Live alerts for stock conditions
- **HealthService** - API health checks

## Response Format

All methods return arrays containing the API response data. The structure depends on the specific endpoint. Please refer to the [Invezgo API Documentation](https://invezgo.com) for detailed response structures.

## Rate Limiting

The API has rate limits based on your subscription package. When rate limits are exceeded, a `RateLimitException` is thrown. You should implement retry logic with exponential backoff.

## Support

- **Website:** https://invezgo.com
- **Email:** admin@invezgo.com
- **API Documentation:** https://invezgo.com
- **API Key Settings:** https://invezgo.com/id/setting/api

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Changelog

### 1.0.0
- Initial release
- Full API coverage for Indonesia Stock Data
- All services implemented
- Exception handling
- Laravel integration support
