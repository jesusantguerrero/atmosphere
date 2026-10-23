<?php

use App\Domains\AppCore\Http\Controllers\ApiLoginController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\MobileController;
use App\Http\Controllers\Api\MultiCurrencyTransactionController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\ShoppingListController;
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
    Route::post('/budget/months/{month}/copy-from-previous', [MobileController::class, 'copyFromPrevious'])->name('budget.copy-from-previous')->where('month', '\d{4}-\d{2}');
    Route::post('/budget/ready-to-assign/allocate', [MobileController::class, 'allocateLeftover'])->name('budget.rta.allocate');
    Route::post('/budget/ready-to-assign/cover', [MobileController::class, 'coverOverspend'])->name('budget.rta.cover');

    // Budget groups CRUD
    Route::post('/budget/groups', [MobileController::class, 'createBudgetGroup'])->name('budget.groups.store');
    Route::patch('/budget/groups/{group}', [MobileController::class, 'updateBudgetGroup'])->name('budget.groups.update');
    Route::delete('/budget/groups/{group}', [MobileController::class, 'deleteBudgetGroup'])->name('budget.groups.destroy');

    // Budget categories CRUD
    Route::post('/budget/categories', [MobileController::class, 'createBudgetCategory'])->name('budget.categories.store');
    Route::patch('/budget/categories/{category}', [MobileController::class, 'updateBudgetCategory'])->name('budget.categories.update');
    Route::delete('/budget/categories/{category}', [MobileController::class, 'deleteBudgetCategory'])->name('budget.categories.destroy');
    Route::patch('/budget/categories/{category}/default-role', [MobileController::class, 'setDefaultRole'])->name('budget.categories.default-role');

    // Budget targets CRUD
    Route::post('/budget/categories/{category}/targets', [MobileController::class, 'createBudgetTarget'])->name('budget.targets.store');
    Route::put('/budget/categories/{category}/targets/{target}', [MobileController::class, 'updateBudgetTarget'])->name('budget.targets.update');
    Route::post('/budget/categories/{category}/targets/{target}/complete', [MobileController::class, 'completeBudgetTarget'])->name('budget.targets.complete');
    Route::patch('/budget/categories/{category}/snooze-target', [MobileController::class, 'snoozeBudgetTarget'])->name('budget.targets.snooze');
    Route::delete('/budget/categories/{category}/targets/{target}', [MobileController::class, 'archiveBudgetTarget'])->name('budget.targets.archive');
    Route::get('/budget/categories/{category}/targets/archived', [MobileController::class, 'archivedBudgetTargets'])->name('budget.targets.archived');

    // Category transactions + movements
    Route::get('/budget/categories/{category}/transactions', [MobileController::class, 'categoryTransactions'])->name('budget.categories.transactions');
    Route::get('/budget/categories/{category}/movements', [MobileController::class, 'categoryMovements'])->name('budget.categories.movements');
    Route::delete('/budget/movements/{movement}', [MobileController::class, 'destroyMovement'])->name('budget.movements.destroy');

    Route::get('/calendar', [MobileController::class, 'calendar'])->name('calendar');
    Route::get('/routine', [MobileController::class, 'routine'])->name('routine');
    Route::post('/routine/{plan}/blocks', [RoutineController::class, 'storeBlock'])->name('routine.blocks.store');
    Route::put('/routine/{plan}/blocks/{item}', [RoutineController::class, 'updateBlock'])->name('routine.blocks.update');
    Route::delete('/routine/{plan}/blocks/{item}', [RoutineController::class, 'destroyBlock'])->name('routine.blocks.destroy');
    Route::post('/routine/{plan}/copy-day', [RoutineController::class, 'copyDay'])->name('routine.copy-day');
    Route::get('/routine/{plan}/categories', [MobileController::class, 'routineCategories'])->name('routine.categories');
    Route::put('/routine/{plan}/categories', [RoutineController::class, 'saveCategories'])->name('routine.categories.save');
    Route::get('/transactions', [MultiCurrencyTransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [MultiCurrencyTransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}', [MobileController::class, 'showTransaction'])->name('transactions.show');
    Route::patch('/transactions/{transaction}', [MobileController::class, 'updateTransaction'])->name('transactions.update');

    // Payees & categories for pickers
    Route::get('/payees', [MobileController::class, 'payees'])->name('payees.index');
    Route::post('/payees', [MobileController::class, 'createPayee'])->name('payees.store');
    Route::get('/categories', [MobileController::class, 'categoriesFlat'])->name('categories.index');

    // Accounts CRUD
    Route::post('/accounts', [MobileController::class, 'storeAccount'])->name('accounts.store');
    Route::get('/accounts/{account}', [MobileController::class, 'showAccount'])->name('accounts.show');
    Route::patch('/accounts/{account}', [MobileController::class, 'updateAccount'])->name('accounts.update');
    Route::put('/accounts/{account}/close', [MobileController::class, 'closeAccount'])->name('accounts.close');

    // Reconciliation
    Route::get('/accounts/{account}/reconciliation', [MobileController::class, 'getReconciliation'])->name('accounts.reconciliation');
    Route::post('/accounts/{account}/reconciliation', [MobileController::class, 'storeReconciliation'])->name('accounts.reconciliation.store');
    Route::get('/reconciliations', [MobileController::class, 'reconciliationHub'])->name('reconciliation.hub');
    Route::get('/accounts/{account}/reconciliations', [MobileController::class, 'accountReconciliations'])->name('accounts.reconciliations');
    Route::get('/accounts/{account}/balance-at', [MobileController::class, 'accountBalanceAt'])->name('accounts.balance-at');
    Route::patch('/reconciliations/{reconciliation}', [MobileController::class, 'updateReconciliation'])->name('reconciliation.update');
    Route::put('/reconciliations/{reconciliation}/sync-transactions', [MobileController::class, 'syncReconciliation'])->name('reconciliation.sync');
    Route::put('/reconciliations/{reconciliation}/entries/{entry}/check', [MobileController::class, 'checkReconciliationEntry'])->name('reconciliation.entry.check');
    Route::put('/reconciliations/{reconciliation}/adjustment', [MobileController::class, 'saveReconciliationAdjustment'])->name('reconciliation.adjustment');
    Route::delete('/reconciliations/{reconciliation}', [MobileController::class, 'deleteReconciliation'])->name('reconciliation.destroy');

    Route::post('/events', [MobileController::class, 'storeEvent'])->name('events.store');
    Route::patch('/events/{planner}', [MobileController::class, 'updateEvent'])->name('events.update');
    Route::delete('/events/{planner}', [MobileController::class, 'deleteEvent'])->name('events.destroy');
    Route::patch('/events/{planner}/complete', [MobileController::class, 'completeEvent'])->name('events.complete');
    Route::patch('/events/{planner}/cancel', [MobileController::class, 'cancelEvent'])->name('events.cancel');
    Route::patch('/events/{planner}/reopen', [MobileController::class, 'reopenEvent'])->name('events.reopen');
    Route::post('/transfers', [MobileController::class, 'transfer'])->name('transfers.store');

    // Chores (household tasks)
    Route::get('/chores', [MobileController::class, 'chores'])->name('chores.index');
    Route::patch('/chores/{item}/toggle', [MobileController::class, 'toggleChore'])->name('chores.toggle');
    Route::post('/chores', [MobileController::class, 'storeChore'])->name('chores.store');
    Route::patch('/chores/{item}', [MobileController::class, 'updateChore'])->name('chores.update');
    Route::delete('/chores/{item}', [MobileController::class, 'deleteChore'])->name('chores.destroy');

    // Meals
    Route::get('/meals', [MobileController::class, 'meals'])->name('meals.index');
    Route::post('/meals/plan', [MobileController::class, 'storeMealPlan'])->name('meals.plan.store');
    Route::patch('/meals/plan/{planner}', [MobileController::class, 'updateMealPlan'])->name('meals.plan.update');
    Route::delete('/meals/plan/{planner}', [MobileController::class, 'deleteMealPlan'])->name('meals.plan.destroy');
    Route::get('/meals/recipes', [MobileController::class, 'recipes'])->name('meals.recipes');
    Route::post('/meals/recipes', [MobileController::class, 'storeRecipe'])->name('meals.recipes.store');
    Route::patch('/meals/recipes/{meal}', [MobileController::class, 'updateRecipe'])->name('meals.recipes.update');
    Route::delete('/meals/recipes/{meal}', [MobileController::class, 'deleteRecipe'])->name('meals.recipes.destroy');
    Route::post('/meals/recipes/{meal}/favorite', [MobileController::class, 'toggleRecipeFavorite'])->name('meals.recipes.favorite');
    Route::post('/meals/recipes/{meal}/shopping-list', [MobileController::class, 'recipeToShoppingList'])->name('meals.recipes.shopping-list');
    Route::get('/meals/random', [MobileController::class, 'randomMeal'])->name('meals.random');

    // Shopping list
    Route::get('/shopping', [MobileController::class, 'shoppingList'])->name('shopping.index');
    Route::post('/shopping/lists', [ShoppingListController::class, 'createList'])->name('shopping.lists.create');
    Route::post('/shopping/import', [ShoppingListController::class, 'import'])->name('shopping.import');
    Route::put('/shopping/{plan}', [ShoppingListController::class, 'renameList'])->name('shopping.rename');
    Route::delete('/shopping/{plan}', [ShoppingListController::class, 'deleteList'])->name('shopping.delete');
    Route::post('/shopping/{plan}/stages', [MobileController::class, 'addShoppingStage'])->name('shopping.stages.add');
    Route::patch('/shopping/{plan}/stages/{stage}', [MobileController::class, 'updateShoppingStage'])->name('shopping.stages.update');
    Route::delete('/shopping/{plan}/stages/{stage}', [MobileController::class, 'deleteShoppingStage'])->name('shopping.stages.delete');
    Route::post('/shopping/{plan}/items', [MobileController::class, 'addShoppingItem'])->name('shopping.items.add');
    Route::patch('/shopping/{plan}/items/{item}', [MobileController::class, 'updateShoppingItem'])->name('shopping.items.update');
    Route::delete('/shopping/{plan}/items/{item}', [MobileController::class, 'deleteShoppingItem'])->name('shopping.items.delete');
    Route::post('/shopping/{plan}/items/{item}/cycle', [MobileController::class, 'cycleShoppingItem'])->name('shopping.items.cycle');
    Route::post('/shopping/{plan}/reset', [MobileController::class, 'resetShoppingList'])->name('shopping.reset');
    Route::post('/shopping/{plan}/check-all', [MobileController::class, 'checkAllShoppingItems'])->name('shopping.check-all');
    Route::post('/shopping/{plan}/share', [MobileController::class, 'shareShoppingList'])->name('shopping.share');
    Route::delete('/shopping/{plan}/share', [MobileController::class, 'unshareShoppingList'])->name('shopping.unshare');
    Route::post('/shopping/{plan}/sessions/start', [MobileController::class, 'startShoppingSession'])->name('shopping.sessions.start');
    Route::post('/shopping/{plan}/sessions/finish', [MobileController::class, 'finishShoppingSession'])->name('shopping.sessions.finish');
    Route::patch('/shopping/{plan}/visits/{session}', [MobileController::class, 'updateShoppingVisit'])->name('shopping.visits.update')->whereUuid('session');

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
