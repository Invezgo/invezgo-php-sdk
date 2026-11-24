# Invezgo PHP SDK

[![Packagist](https://img.shields.io/packagist/v/invezgo/sdk.svg)](https://packagist.org/packages/invezgo/sdk)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-777bb4.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

SDK resmi untuk [Invezgo API](https://invezgo.com), penyedia data pasar modal Indonesia (BEI/IDX). SDK ini menyediakan akses bertipe ke seluruh endpoint Invezgo API, dilengkapi cache in-memory dan rotasi API key otomatis.

[Dokumentasi API](https://api.invezgo.com/documentation) · [Referensi SDK](docs/API.md) · [Changelog](CHANGELOG.md)

## Daftar Isi

- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Penggunaan](#penggunaan)
- [Konfigurasi](#konfigurasi)
- [Memanggil Endpoint](#memanggil-endpoint)
- [Tipe Data](#tipe-data)
- [Caching](#caching)
- [Rotasi API Key](#rotasi-api-key)
- [Penanganan Error](#penanganan-error)
- [Integrasi Laravel](#integrasi-laravel)
- [Pagination](#pagination)
- [Webhook](#webhook)
- [WebSocket (Real-time)](#websocket-real-time)
- [Praktik yang Disarankan](#praktik-yang-disarankan)
- [Pengembangan](#pengembangan)
- [Dukungan](#dukungan)
- [Lisensi](#lisensi)

## Persyaratan

- PHP 8.1 atau lebih baru dengan ekstensi `json`
- [Guzzle](https://docs.guzzlephp.org/) 7 (terpasang otomatis melalui Composer)
- API key Invezgo, dapat dibuat di [halaman pengaturan API](https://invezgo.com/id/setting/api)

## Instalasi

```bash
composer require invezgo/sdk
```

## Penggunaan

```php
<?php

require 'vendor/autoload.php';

use Invezgo\InvezgoClient;

$client = new InvezgoClient(getenv('INVEZGO_API_KEY'));

$stocks = $client->analysis->getStockList();
$info = $client->analysis->getInformation('BBCA');
$top = $client->analysis->getTopChange(date: '2024-12-30');

echo $top->gain[0]->code, ' ', $top->gain[0]->change, PHP_EOL;
```

> **Penting:** simpan API key di environment variable atau secret manager, dan jangan menampilkannya di sisi klien.

## Konfigurasi

```php
use Invezgo\Cache\CacheConfig;
use Invezgo\InvezgoClient;

$client = new InvezgoClient(
    apiKey: getenv('INVEZGO_API_KEY'),
    additionalApiKeys: [getenv('INVEZGO_API_KEY_2')],
    timeout: 30,
    cache: new CacheConfig(ttl: 60, realtimeTtl: 5),
);
```

| Opsi | Tipe | Default | Keterangan |
|---|---|---|---|
| `apiKey` | `string` | wajib | API key utama. |
| `additionalApiKeys` | `list<string>` | `[]` | API key cadangan, digunakan saat terjadi `429`. Lihat [Rotasi API Key](#rotasi-api-key). |
| `baseUrl` | `?string` | `https://api.invezgo.com` | Base URL API. |
| `timeout` | `float` | `30` | Timeout per request, dalam detik. |
| `cache` | `bool\|CacheConfig` | `true` | Konfigurasi cache. `false` untuk menonaktifkan. |
| `CacheConfig::$ttl` | `float` | `60` | TTL endpoint reguler, dalam detik. |
| `CacheConfig::$realtimeTtl` | `float` | `5` | TTL endpoint realtime, dalam detik. |
| `CacheConfig::$maxEntries` | `int` | `1000` | Jumlah maksimum entri cache. |
| `CacheConfig::$maxBytes` | `int` | `33554432` | Ukuran maksimum cache (32 MiB). |
| `httpClient` | `?GuzzleHttp\ClientInterface` | Guzzle baru | Client HTTP alternatif, misalnya untuk proxy atau middleware. |

Buat satu instance client dan gunakan kembali di seluruh aplikasi. Cache dan status rotasi API key disimpan per instance.

## Memanggil Endpoint

Endpoint dikelompokkan berdasarkan resource, yang tersedia sebagai properti `readonly` pada client:

| Resource | Cakupan |
|---|---|
| `analysis` | Daftar saham, broker, dan indeks; harga, chart, intraday, order book, running trade; broker summary; pemegang saham; laporan keuangan; kalender aksi korporasi; berita dan keterbukaan informasi |
| `batch` | Order book dan data intraday untuk beberapa saham dalam satu request |
| `search` | Pencarian saham dan pengguna |
| `screener` | Eksekusi screener dan pengelolaan preset |
| `usage` | Pemakaian dan sisa kuota API |
| `watchlists` | Watchlist dan grup watchlist |
| `journals` | Jurnal transaksi |
| `portfolios` | Portofolio |
| `alerts` | Alert berbasis formula |
| `posts` | Post, komentar, like, dan vote komunitas |
| `trades` | Realized trade |

Setiap method mengikuti pola berikut:

```
$client-><resource>-><method>($pathParam, ..., $body, queryParam: ...)
```

| Argumen | Keterangan |
|---|---|
| Path parameter | Argumen posisional, sesuai urutan di URL. |
| Body | Request body untuk `POST`, `PUT`, `PATCH`, dan `DELETE`, berupa model `Invezgo\Model\*Request` atau array asosiatif. |
| Query parameter | Named argument dalam camelCase, misalnya `filter_column` menjadi `filterColumn:`. Parameter opsional dapat dihilangkan. |
| Tanggal | String `YYYY-MM-DD` atau `DateTimeInterface`. |
| Enum | Instance `Invezgo\Enum\*` atau string. |

```php
use Invezgo\Enum\Investor;
use Invezgo\Enum\Market;
use Invezgo\Model\AlertRequest;
use Invezgo\Model\ScreenRequest;

// Path parameter dan query parameter
$stalker = $client->analysis->getBrokerStalker(
    'YP',
    'BBCA',
    from: '2024-12-01',
    to: '2024-12-31',
    investor: Investor::All,
    market: Market::RG,
);

// Request body
$rows = $client->screener->screen(new ScreenRequest(
    formula: 'close > prev AND volume > 1000000',
    category: ['SYARIAH'],
));

$client->alerts->create(new AlertRequest(
    name: 'BBCA di atas 10.000',
    every: 'FIVE_MINUTES',
    send: 'IN',
    formula: 'close > 10000',
));

// Beberapa saham sekaligus, dihitung sebagai satu request
$books = $client->batch->getOrderBook('BBCA|BBRI|BMRI');
```

Daftar lengkap method, parameter, nilai default, dan tipe kembalian tersedia di [docs/API.md](docs/API.md).

## Tipe Data

Setiap response dikembalikan sebagai model `final` dengan properti `public readonly`. Nama properti sama dengan nama field di API.

```php
use Invezgo\Enum\FilterColumn;
use Invezgo\Enum\FilterOperator;

// Invezgo\Model\TopChangeResponse
$top = $client->analysis->getTopChange(
    date: new DateTimeImmutable('2024-12-30'),
    filterColumn: FilterColumn::Change,
    filterOperator: FilterOperator::Gt,
    filterValue: 5,
);

foreach ($top->gain as $row) {
    echo $row->code, ' ', $row->calculated_value, PHP_EOL;
}
```

Ketentuan tipe:

| Kasus | Representasi |
|---|---|
| Parameter enum | Backed enum `Invezgo\Enum\*`, string juga diterima |
| Field nullable | `?T` |
| Response array | `list<Model>` |
| Response yang dapat bernilai `null` | `?Model`, misalnya `getShareholderClassifyTable()` |
| Angka yang dapat dikirim sebagai string | Dikonversi ke `int` atau `float` |
| Field dinamis | `array<string, ...>`, misalnya `ScreenerScreenItem::$values` |

Model dapat dikonversi kembali ke format API melalui `toArray()` atau `json_encode()`, dan dibuat dari array melalui `fromArray()`:

```php
$usage = $client->usage->getApiUsage();

$array = $usage->toArray();
$json = json_encode($usage);
```

## Caching

Cache in-memory aktif secara default untuk mengurangi pemakaian kuota dan latensi.

- Hanya request `GET` yang disimpan. Cache key terdiri dari path dan query string yang telah dinormalisasi.
- Cache menggunakan algoritma LRU dengan batas jumlah entri (`maxEntries`) dan total ukuran (`maxBytes`). Entri yang paling lama tidak digunakan akan dihapus saat salah satu batas terlampaui.
- Endpoint realtime (order book, running trade, intraday, chart, batch, dan sejenisnya) menggunakan `realtimeTtl`. Endpoint lainnya menggunakan `ttl`.
- Endpoint `usage` dan request selain `GET` tidak disimpan di cache.
- Setiap request `POST`, `PUT`, `PATCH`, atau `DELETE` yang berhasil akan menghapus cache data personal (`watchlists`, `journals`, `portfolios`, `trades`, `alerts`, `screener`).

```php
new InvezgoClient($apiKey, cache: false);                             // nonaktifkan cache
new InvezgoClient($apiKey, cache: new CacheConfig(realtimeTtl: 0));   // nonaktifkan cache untuk endpoint realtime
new InvezgoClient($apiKey, cache: new CacheConfig(ttl: 300));         // TTL endpoint reguler 5 menit

$client->clearCache();                                                // hapus seluruh cache
```

Cache disimpan di memori proses PHP. Pada PHP-FPM, cache hanya berlaku selama satu request. Untuk memanfaatkan cache antar request, daftarkan client sebagai singleton pada worker yang berjalan lama, seperti Laravel Octane, RoadRunner, atau queue worker.

## Rotasi API Key

Setiap API key memiliki batas request per menit untuk setiap endpoint dan kuota bulanan. Jika Anda memiliki lebih dari satu API key, tambahkan sebagai cadangan:

```php
$client = new InvezgoClient(
    getenv('INVEZGO_API_KEY'),
    additionalApiKeys: [getenv('INVEZGO_API_KEY_2'), getenv('INVEZGO_API_KEY_3')],
);
```

Ketika API mengembalikan `429 Too Many Requests`:

1. Lama penundaan diambil dari header `Retry-After`. Jika tidak tersedia, digunakan `X-RateLimit-Reset`, kemudian 60 detik.
2. Untuk batas per menit, API key ditangguhkan hanya pada endpoint tersebut. Untuk kuota bulanan, API key ditangguhkan pada seluruh endpoint.
3. Request dikirim ulang menggunakan API key berikutnya yang tersedia.
4. API key utama digunakan kembali setelah masa penangguhan berakhir.
5. Jika seluruh API key sedang ditangguhkan, SDK melempar `RateLimitException` tanpa mengirim request.

Rotasi hanya dilakukan untuk status `429`. Untuk endpoint data personal seperti watchlist dan portofolio, gunakan API key dari akun yang sama.

Sisa kuota dapat diperiksa tanpa mengurangi kuota:

```php
$usage = $client->usage->getApiUsage();

echo $usage->usage, '/', $usage->limit, ', sisa ', $usage->remaining, ', reset ', $usage->expire, PHP_EOL;
```

## Penanganan Error

Seluruh exception merupakan turunan `Invezgo\Exception\InvezgoException`. Error dari API merupakan turunan `ApiException`.

| Class | Status HTTP | Penyebab |
|---|---|---|
| `ValidationException` | 400, 422 | Parameter atau body tidak valid |
| `AuthenticationException` | 401 | API key tidak valid atau kedaluwarsa |
| `PaymentRequiredException` | 402 | Endpoint tidak termasuk dalam paket langganan |
| `NotFoundException` | 404 | Data tidak ditemukan |
| `RateLimitException` | 429 | Batas request atau kuota tercapai pada seluruh API key |
| `ServerException` | 5xx | Kesalahan pada server |
| `ApiException` | lainnya | Status HTTP lain di luar 2xx |
| `ConnectionException` | — | Kegagalan jaringan atau timeout |

`ApiException` menyediakan `getMessage()` serta properti `$status`, `$error`, `$requestId`, `$path`, dan `$body`. `RateLimitException` juga menyediakan `$retryAfter` (detik), `$limit`, `$remaining`, `$reset`, dan `$quotaExceeded`.

```php
use Invezgo\Exception\ApiException;
use Invezgo\Exception\ConnectionException;
use Invezgo\Exception\RateLimitException;
use Invezgo\Exception\ValidationException;

try {
    $summary = $client->analysis->getSummaryStock(
        'BBCA',
        from: '2024-12-01',
        to: '2024-12-31',
        investor: 'all',
        market: 'RG',
    );
} catch (RateLimitException $e) {
    error_log("Rate limit tercapai, coba lagi dalam {$e->retryAfter} detik");
} catch (ValidationException $e) {
    error_log('Parameter tidak valid: ' . $e->getMessage());
} catch (ApiException $e) {
    error_log("{$e->status} {$e->getMessage()} (request {$e->requestId})");
} catch (ConnectionException $e) {
    error_log('Gagal terhubung: ' . $e->getMessage());
}
```

## Integrasi Laravel

Tambahkan konfigurasi di `config/services.php`:

```php
'invezgo' => [
    'key' => env('INVEZGO_API_KEY'),
    'additional_keys' => array_filter(explode(',', (string) env('INVEZGO_ADDITIONAL_API_KEYS', ''))),
],
```

Daftarkan client sebagai singleton di service provider:

```php
use Illuminate\Support\ServiceProvider;
use Invezgo\InvezgoClient;

class InvezgoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InvezgoClient::class, fn () => new InvezgoClient(
            config('services.invezgo.key'),
            additionalApiKeys: config('services.invezgo.additional_keys', []),
        ));
    }
}
```

Gunakan melalui dependency injection:

```php
use Invezgo\InvezgoClient;

class StockController extends Controller
{
    public function show(InvezgoClient $invezgo, string $code)
    {
        return response()->json($invezgo->analysis->getInformation($code));
    }
}
```

Contoh lengkap tersedia di [examples/laravel-example.php](examples/laravel-example.php).

## Pagination

Endpoint dengan pagination mengembalikan `page`, `totalPage`, dan `nextPage`. Nilai `nextPage` adalah `null` pada halaman terakhir.

```php
$page = 1;

while ($page !== null) {
    $res = $client->analysis->getNews(page: $page, limit: 50);
    foreach ($res->data as $item) {
        echo $item->title, PHP_EOL;
    }
    $page = $res->nextPage;
}
```

## Webhook

Webhook mengirim notifikasi `POST` ke server Anda saat data Invezgo diperbarui (`update.*`), saat Live Alert terpicu (`alert.*`), serta peringatan kuota (`quota.*`). Fitur ini masih beta. `Webhook::verify()` memeriksa header `X-Invezgo-Timestamp` (toleransi 300 detik) dan `X-Invezgo-Signature` (HMAC-SHA256 dengan perbandingan waktu-konstan), lalu mengembalikan `WebhookEvent`. Fungsi ini tidak memerlukan client maupun API key.

Gunakan body mentah sebelum di-parse sebagai JSON. Nama header dicocokkan tanpa memperhatikan huruf besar/kecil, dan nilainya boleh berupa string atau array (format PSR-7 maupun Laravel).

```php
use Invezgo\Exception\WebhookVerificationException;
use Invezgo\Webhook\AlertTriggeredData;
use Invezgo\Webhook\UpdateData;
use Invezgo\Webhook\Webhook;

// Laravel: $request->getContent() dan $request->headers->all()
try {
    $event = Webhook::verify(file_get_contents('php://input'), getallheaders(), getenv('INVEZGO_WEBHOOK_SECRET'));
} catch (WebhookVerificationException $e) {
    http_response_code($e->reason === 'signature' ? 401 : 400); // reason: timestamp, signature, atau payload
    exit;
}

http_response_code(204); // balas dalam 10 detik, proses event di background; deduplikasi dengan $event->id

$data = $event->typedData();
match ($event->name()) {
    'update.summary' => refreshSummary($data instanceof UpdateData ? $data->date : null),
    'alert.triggered' => notify($data instanceof AlertTriggeredData ? $data->stocksIn : []),
    default => null, // abaikan event yang belum dikenali
};
```

`typedData()` mengembalikan `UpdateData`, `AlertTriggeredData`, `AlertDisabledData`, `PingData`, atau `QuotaData` sesuai event, dan `null` untuk event yang belum dikenali. Data mentah tetap tersedia di `$event->data`. Gunakan `Webhook::parse($rawBody)` untuk membaca body yang sudah diverifikasi.

## WebSocket (Real-time)

WebSocket menyalurkan running trade, order book, quote saham, dan indeks secara real-time melalui `wss://api.invezgo.com/ws`. Fitur ini masih beta dan memerlukan paket Developer **Elite**. Pasang pustaka tambahan berikut:

```bash
composer require phrity/websocket:^3.0
```

`$client->realtime()` memakai API key utama dan URL yang diturunkan dari `baseUrl`. API key dikirim melalui header `Authorization`, tidak melalui URL. `run()` bersifat blocking dan memanggil callback hingga `close()` dipanggil.

```php
use Invezgo\Realtime\IndexMessage;
use Invezgo\Realtime\OrderbookMessage;
use Invezgo\Realtime\StockMessage;
use Invezgo\Realtime\TradesMessage;

$ws = $client->realtime();

$ws->onTrades(function (TradesMessage $msg) {
    foreach ($msg->data as $trade) {
        echo $trade->code, ' ', $trade->price, ' ', $trade->lot, ' ', $trade->side, PHP_EOL;
    }
})->onOrderbook(function (OrderbookMessage $msg) {
    echo $msg->code, ' ', $msg->side, ' ', count($msg->levels), ' level', PHP_EOL;
})->onStock(function (StockMessage $msg) {
    echo $msg->code, ' ', $msg->last, ' ', $msg->changePct, PHP_EOL;
})->onIndex(function (IndexMessage $msg) {
    echo $msg->code, ' ', $msg->last, ' ', $msg->changePct, PHP_EOL;
})->onReconnect(function (int $attempts) use ($client) {
    // isi data yang terlewat melalui REST, misalnya $client->analysis->getStockRunningTrade(...)
});

$ws->subscribeTrades(['BBCA', 'TLKM'], minLot: 500);
$ws->subscribeOrderbook(['BBCA']);
$ws->subscribeStock(['BBCA']);
$ws->subscribeIndex(['COMPOSITE', 'LQ45']);
$ws->run();
```

| Method | Fungsi |
|---|---|
| `subscribeTrades(?array $codes = null, ?int $minLot = null, ?int $minValue = null)` | Running trade; `null` untuk seluruh saham, maksimal 200 kode |
| `subscribeOrderbook(array $codes)` | Order book, wajib diisi, maksimal 20 kode |
| `subscribeStock(array $codes)` | Quote saham (OHLC, volume, nilai, frekuensi, IEP/IEV), wajib diisi, maksimal 50 kode |
| `subscribeIndex(?array $codes = null)` | Quote indeks; `null` untuk seluruh indeks, maksimal 100 kode |
| `unsubscribe(string $channel, ?array $codes = null)` | Berhenti berlangganan `trades`, `orderbook`, `stock`, atau `index` |
| `ping()`, `close()` | Memeriksa koneksi; menutup koneksi dan mengakhiri `run()` |

Callback yang tersedia: `onConnected`, `onSubscribed`, `onTrades`, `onOrderbook`, `onStock`, `onIndex`, `onPong`, `onLagged`, `onError`, `onMessage` (seluruh pesan, termasuk tipe yang belum dikenali), `onClose`, dan `onReconnect`.

- Langganan disimpan oleh client dan dikirim setelah pesan `connected`, termasuk setelah sambung ulang. Kode saham dan indeks diubah menjadi huruf besar.
- Field `StockMessage` dan `IndexMessage` yang belum tersedia dari bursa bernilai `null`. Kode indeks pada `IndexMessage` mengikuti penulisan bursa (misalnya `Investor33`); bandingkan dengan `strcasecmp`.
- Sambung ulang otomatis menggunakan jeda 1, 2, 4, 8, 16, hingga 30 detik. Nonaktifkan dengan `$client->realtime(reconnect: false)`.
- Sambung ulang tidak dilakukan setelah `close()`, kode penutupan `4400` (pesan klien tidak valid), atau penolakan handshake `401` (`AuthenticationException`) dan `403` (`PaymentRequiredException`, paket Elite tidak aktif).
- Client juga dapat dibuat tanpa `InvezgoClient`: `new RealtimeClient($apiKey, $url)`.

## Praktik yang Disarankan

- Gunakan satu instance client untuk seluruh aplikasi.
- Simpan API key di environment variable atau secret manager.
- Gunakan format tanggal `YYYY-MM-DD`. Data end-of-day tersedia setelah pukul 18.00 WIB.
- Field waktu bersufiks `Z` menggunakan waktu WIB (UTC+7); jangan dikonversi ke zona waktu lain.
- Gunakan resource `batch` untuk mengambil data beberapa saham sekaligus.
- Saat menerima `RateLimitException`, tunggu sesuai `$retryAfter` sebelum mencoba kembali.
- Untuk polling data realtime, sesuaikan `realtimeTtl` dengan interval polling.

Contoh lengkap tersedia di direktori [examples](examples/).

## Pengembangan

File di `src/Model/`, `src/Enum/`, `src/Resource/`, dan `docs/API.md` dihasilkan dari spesifikasi OpenAPI dan tidak boleh diubah secara manual.

```bash
python ../codegen/generate.py php   # generate ulang kode dari OpenAPI
composer install
composer test                       # unit test (PHPUnit)
composer analyse                    # analisis statis (PHPStan level 8)
```

## Dukungan

Sertakan nilai `$requestId` dari exception saat menghubungi tim dukungan Invezgo.

- Website: [invezgo.com](https://invezgo.com)
- Dokumentasi API: [api.invezgo.com/documentation](https://api.invezgo.com/documentation)

## Lisensi

Dirilis di bawah [MIT License](LICENSE).
