<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardApiController;
use App\Domains\AppCore\Http\Controllers\ApiLoginController;

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

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/dashboard', DashboardApiController::class)->name('dashboards');
});

// Multi-currency transaction endpoints
Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->prefix('api')->name('api.')->group(function () {
    Route::controller(\App\Http\Controllers\Api\MultiCurrencyTransactionController::class)->group(function () {
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

Route::post('/sanctum/token', [ApiLoginController::class, 'login']);