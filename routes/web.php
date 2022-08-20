<?php

use App\Http\Controllers\Api\AccountApiController;
use App\Http\Controllers\Api\AutomationController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\CurrencyApiController;
use App\Http\Controllers\Api\IngredientApiController;
use App\Http\Controllers\Api\LabelApiController;
use App\Http\Controllers\Api\PayeeApiController;
use App\Http\Controllers\Api\RecipeApiController;
use App\Http\Controllers\Api\TimezonesApiController;
use App\Http\Controllers\BudgetCategoryController;
use App\Http\Controllers\BudgetMonthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\Jetstream\TeamInvitationController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\TransactionDraftController;
use App\Http\Controllers\TrendController;
use Freesgen\Atmosphere\Http\Controllers\SettingsController;
use Freesgen\Atmosphere\Http\OnboardingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

if (config('app.env') == 'production') {
    URL::forceScheme('https');
}

Route::get('/', fn () => Inertia::render('Landing/Index'));

Route::resource('onboarding', OnboardingController::class)->middleware(['auth:sanctum', 'atmosphere.unteamed', 'verified']);

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    /**
     *  Jetstream & Settings Section
     */

    // Jetstream teams invitations override
    Route::put('/team-invitations/{invitation}', [TeamInvitationController::class, 'resend'])->name('team-invitations.resend');

    // Settings routes
    Route::controller(SettingsController::class)->group(function () {
        Route::resource('/settings', SettingsController::class);
        Route::get('/settings/tab/{tabName}', 'index');
        Route::get('/settings/{name}',  'section');
    });

    /**************************************************************************************
     *                                  Dashboard Section
    ***************************************************************************************/

    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard');
        Route::get('/settings/integrations', [DashboardController::class, 'integrations'])->name('settings.integrations');
        Route::post('/services/google', 'google')->name('services.google');
    });

    /**************************************************************************************
     *                                  Meal Section
    ***************************************************************************************/

    //  Meal related routes
    Route::controller(MealController::class)->group(function () {
        Route::resource('/meals', MealController::class);
        Route::post('/meals/add-plan','addPlan')->name('meals.addPlan');
        Route::get('/meals-random', 'random')->name('meals.random');
    });

    Route::controller(IngredientController::class)->group(function () {
        Route::resource('/ingredients', IngredientController::class);
    });

    Route::resource('/meal-planner', PlannerController::class);

   /**************************************************************************************
     *                               Finance Section
    ***************************************************************************************/

    // Budgeting & Goals routes
    Route::resource('/budgets', BudgetCategoryController::class);
    Route::post('/budgets/{categoryId}/months/{month}', [BudgetMonthController::class, 'assign'])->name("budget.assignment");
    Route::controller(GoalController::class)->group(function () {
        Route::resource('/goals', GoalController::class);
    });


    // Account && Transactions
    Route::controller(TransactionDraftController::class)->group(function () {
        Route::patch('/planned-transactions/{id}/mark-as-paid', 'markAsPaid')->name("transactions.mark-as-paid");
        Route::post('/planned-transactions', 'addPlannedTransaction')->name("budget.planned-transaction");
        Route::get('/finance/transaction-drafts', 'index')->name("budget.planned-transaction");
    });

    // Finance dashboard related routes
    Route::controller(FinanceController::class)->group(function () {
        Route::get('/finance', 'index')->name('finance');
        Route::get('/finance/transactions', 'transactions')->name('finance.transactions');
        Route::get('/finance/{accountId}/transactions', 'transactions')->name('finance.account.transactions');
        Route::post('/finance/import', 'importTransactions')->name('finance.import');
        Route::post('/finance/import-budget', 'importMonthBudgets')->name('finance.import.budget');
    });

    Route::get('/trends', [TrendController::class, 'index'])->name('finance.trends');
    Route::get('/trends/{name}', [TrendController::class, 'index'])->name('finance.trend-section');
});


/**************************************************************************************
 *                                  API Section
 ***************************************************************************************/

Route::middleware(['auth:sanctum'])->prefix('/api')->group(function () {
    //timezones
    Route::get('/timezones', [TimezonesApiController::class, 'index']);
    // currencies
    Route::get('/currencies', [CurrencyApiController::class, 'index']);

    // Jetstream teams rewrite
    Route::patch('/v2/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])->name('team-invitations.accept-internal');
    Route::delete('/v2/team-invitations/{invitation}', [TeamInvitationController::class, 'reject'])->name('team-invitations.reject');
});

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->prefix('/api')->group(function () {
    //  automation routes
    Route::controller(AutomationController::class)->group(function () {
        Route::apiResource('automation', AutomationController::class);
        Route::post('/automation/{id}/run', 'run');
        Route::post('/automation/run-all', 'runAll');
    });

    //  accounts and transactions
    Route::resource('payees', PayeeApiController::class);
    Route::patch('/accounts', [AccountApiController::class,  'bulkUpdate']);
    Route::resource('categories', CategoryApiController::class);
    Route::patch('/categories', [CategoryApiController::class,  'bulkUpdate']);

    //  recipes & ingredients
    Route::resource('recipes', RecipeApiController::class);
    Route::controller(IngredientApiController::class)->group(function() {
        Route::resource('ingredients', IngredientApiController::class);
        Route::post('/ingredients/{id}/labels', 'addLabel')->name('ingredients.label.add');
    });

    // Labels
    Route::resource('labels', LabelApiController::class);
});
