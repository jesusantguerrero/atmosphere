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
    // Session & profile
    Route::get('/me', [MobileController::class, 'me'])->name('me');
    Route::delete('/session', [MobileController::class, 'logout'])->name('session.destroy');

    Route::get('/today', [MobileController::class, 'today'])->name('today');
    Route::get('/overview', [MobileController::class, 'overview'])->name('overview');
    Route::get('/budget', [MobileController::class, 'budget'])->name('budget');
    Route::post('/budget/assign', [MobileController::class, 'assignBudget'])->name('budget.assign');
    Route::post('/budget/move', [MobileController::class, 'moveBudget'])->name('budget.move');
    Route::get('/calendar', [MobileController::class, 'calendar'])->name('calendar');
    Route::get('/routine', [MobileController::class, 'routine'])->name('routine');
    Route::get('/transactions', [MultiCurrencyTransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [MultiCurrencyTransactionController::class, 'store'])->name('transactions.store');
    Route::post('/events', [MobileController::class, 'storeEvent'])->name('events.store');
    Route::patch('/events/{planner}/complete', [MobileController::class, 'completeEvent'])->name('events.complete');
    Route::post('/transfers', [MobileController::class, 'transfer'])->name('transfers.store');

    // Chores (household tasks)
    Route::get('/chores', [MobileController::class, 'chores'])->name('chores.index');
    Route::patch('/chores/{item}/toggle', [MobileController::class, 'toggleChore'])->name('chores.toggle');
    Route::post('/chores', [MobileController::class, 'storeChore'])->name('chores.store');

    // Meals
    Route::get('/meals', [MobileController::class, 'meals'])->name('meals.index');
    Route::get('/meals/recipes', [MobileController::class, 'recipes'])->name('meals.recipes');

    // Shopping list
    Route::get('/shopping', [MobileController::class, 'shoppingList'])->name('shopping.index');
    Route::post('/shopping/{plan}/items', [MobileController::class, 'addShoppingItem'])->name('shopping.items.add');
    Route::post('/shopping/{plan}/items/{item}/cycle', [MobileController::class, 'cycleShoppingItem'])->name('shopping.items.cycle');

    // Inbox (draft transactions)
    Route::post('/transactions/{transaction}/approve', [MobileController::class, 'approveTransaction'])->name('transactions.approve');
    Route::delete('/transactions/{transaction}', [MobileController::class, 'deleteTransaction'])->name('transactions.delete');
    Route::post('/transactions/bulk-delete', [MobileController::class, 'bulkDeleteTransactions'])->name('transactions.bulk-delete');

    // Occurrences
    Route::get('/occurrences', [MobileController::class, 'occurrences'])->name('occurrences.index');
    Route::post('/occurrences/{occurrence}/instances', [MobileController::class, 'addOccurrenceInstance'])->name('occurrences.instances.add');
    Route::delete('/occurrences/{occurrence}/instances', [MobileController::class, 'removeOccurrenceInstance'])->name('occurrences.instances.remove');

    // Insights (Tendencias)
    Route::get('/insights', [MobileController::class, 'insights'])->name('insights');

    // Notifications
    Route::get('/notifications', [MobileController::class, 'notifications'])->name('notifications.index');
    Route::put('/notifications/{notification}/read', [MobileController::class, 'markNotificationRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [MobileController::class, 'markAllNotificationsRead'])->name('notifications.read-all');

    // Account deletion (store requirement)
    Route::delete('/account', [MobileController::class, 'deleteAccount'])->name('account.delete');

    // Email parsing (share-to-app)
    Route::post('/parse-email', [MobileController::class, 'parseEmail'])->name('parse-email');
    Route::get('/inbound-email/address', [MobileController::class, 'inboundEmailAddress'])->name('inbound-email.address');
});

// Public mobile endpoints (no Bearer required)
Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::post('/auth/google', [MobileController::class, 'authGoogle'])->name('auth.google');
    Route::post('/auth/apple', [MobileController::class, 'authApple'])->name('auth.apple');

    // Inbound email webhook (user resolved from recipient address)
    Route::post('/inbound-email', [MobileController::class, 'inboundEmail'])->name('inbound-email');
});

Route::post('/sanctum/token', [ApiLoginController::class, 'login']);
