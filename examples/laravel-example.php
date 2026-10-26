<?php

/**
 * Laravel integration example (copy the pieces into your application).
 *
 * 1. config/services.php
 *
 *    'invezgo' => [
 *        'key' => env('INVEZGO_API_KEY'),
 *        'additional_keys' => array_filter(explode(',', (string) env('INVEZGO_ADDITIONAL_API_KEYS', ''))),
 *        'cache_ttl' => (float) env('INVEZGO_CACHE_TTL', 60),
 *    ],
 *
 * 2. .env
 *
 *    INVEZGO_API_KEY=your-primary-key
 *    INVEZGO_ADDITIONAL_API_KEYS=backup-key-1,backup-key-2
 *
 * 3. Register App\Providers\InvezgoServiceProvider in bootstrap/providers.php (Laravel 11+)
 *    or config/app.php (Laravel 10 and older).
 *
 * The client is bound as a singleton so the in-memory LRU cache and the API key rotation state are shared
 * by every caller. Under Laravel Octane, queue workers or long-running commands they also survive across
 * requests/jobs; under classic PHP-FPM they live for one request.
 */

// ---------------------------------------------------------------- app/Providers/InvezgoServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Invezgo\Cache\CacheConfig;
use Invezgo\InvezgoClient;

class InvezgoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InvezgoClient::class, fn () => new InvezgoClient(
            config('services.invezgo.key'),
            additionalApiKeys: config('services.invezgo.additional_keys', []),
            cache: new CacheConfig(ttl: config('services.invezgo.cache_ttl', 60)),
        ));
    }
}

// ---------------------------------------------------------------- app/Http/Controllers/StockController.php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Invezgo\Exception\InvezgoException;
use Invezgo\Exception\NotFoundException;
use Invezgo\Exception\RateLimitException;
use Invezgo\InvezgoClient;

class StockController extends Controller
{
    public function __construct(private readonly InvezgoClient $invezgo)
    {
    }

    /** GET /stocks */
    public function index()
    {
        return view('stocks.index', ['stocks' => $this->invezgo->analysis->getStockList()]);
    }

    /** GET /stocks/{code} */
    public function show(string $code)
    {
        try {
            return view('stocks.show', [
                'info' => $this->invezgo->analysis->getInformation($code),
                'chart' => $this->invezgo->analysis->getAdvanceChart($code, from: now()->subMonth(), to: now()),
                'shareholders' => $this->invezgo->analysis->getShareholder($code),
            ]);
        } catch (NotFoundException) {
            abort(404);
        } catch (InvezgoException $e) {
            return redirect()->route('stocks.index')->with('error', 'Gagal memuat data saham: ' . $e->getMessage());
        }
    }

    /** GET /api/top-change?date=2024-12-30 — models are JsonSerializable */
    public function topChange(Request $request): JsonResponse
    {
        try {
            return response()->json($this->invezgo->analysis->getTopChange(date: $request->query('date', now()->toDateString())));
        } catch (RateLimitException $e) {
            return response()->json(['error' => $e->getMessage()], 429, ['Retry-After' => (string) $e->retryAfter]);
        } catch (InvezgoException $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }
}

// ---------------------------------------------------------------- routes/web.php
/*
Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
Route::get('/stocks/{code}', [StockController::class, 'show'])->name('stocks.show');
Route::get('/api/top-change', [StockController::class, 'topChange']);
*/
