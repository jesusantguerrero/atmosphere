<?php

use App\Domains\AppCore\Http\Controllers\ApiLoginController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\MobileController;
use App\Http\Controllers\Api\MultiCurrencyTransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/dashboard', DashboardApiController::class)->name('dashboards');
});

// Multi-currency transaction endpoints
Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->prefix('api')->name('api.')->group(function () {
    Route::controller(MultiCurrencyTransactionController::class)->group(function () {
        // Transaction creation and listing
        Route::post('/multi-currency/transactions', 'store')->name('multi-currency.transactions.store');
        Route::get('/multi-currency/transactions', 'index')->name('multi-currency.transactions.index');
        Route::get('/multi-currency/transactions/{transaction}', 'show')->name('multi-currency.transactions.show');

        // Payment processing
        Route::post('/multi-currency/payments', 'processPayment')->name('multi-currency.payments.process');

        // Currency balance endpoints
        Route::get('/accounts/{account}/currency-balances', 'getCurrencyBalances')->name('accounts.currency-balances');
        Route::get('/accounts/{account}/pending-balances', 'getPendingBalances')->name('accounts.pending-balances');
    });
});

// Mobile app surface: clean, token-authenticated URLs under /api/mobile.
// Reuses existing controllers/services; only reshapes payloads for the phone.
Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/today', [MobileController::class, 'today'])->name('today');
    Route::get('/overview', [MobileController::class, 'overview'])->name('overview');
    Route::get('/budget', [MobileController::class, 'budget'])->name('budget');
    Route::get('/calendar', [MobileController::class, 'calendar'])->name('calendar');
    Route::get('/routine', [MobileController::class, 'routine'])->name('routine');
    Route::get('/transactions', [MultiCurrencyTransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [MultiCurrencyTransactionController::class, 'store'])->name('transactions.store');
    Route::post('/events', [MobileController::class, 'storeEvent'])->name('events.store');
    Route::patch('/events/{planner}/complete', [MobileController::class, 'completeEvent'])->name('events.complete');
    Route::post('/transfers', [MobileController::class, 'transfer'])->name('transfers.store');
});

Route::post('/sanctum/token', [ApiLoginController::class, 'login']);
