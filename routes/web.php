<?php

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Meal\MealController;
use App\Http\Controllers\Api\LabelApiController;
use App\Http\Controllers\Api\PayeeApiController;
use App\Http\Controllers\Api\RecipeApiController;
use App\Http\Controllers\Api\AccountApiController;
use App\Http\Controllers\System\ServiceController;
use Freesgen\Atmosphere\Http\OnboardingController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\CurrencyApiController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Meal\IngredientController;
use App\Http\Controllers\Api\TimezonesApiController;
use App\Http\Controllers\Meal\MealPlannerController;
use App\Http\Controllers\System\DashboardController;
use App\Http\Controllers\Api\IngredientApiController;
use App\Http\Controllers\System\UserDeviceController;
use App\Http\Controllers\System\IntegrationController;
use App\Http\Controllers\System\NotificationController;
use App\Http\Controllers\Finance\FinanceLinesController;
use App\Http\Controllers\Finance\FinanceTrendController;
use App\Http\Controllers\System\TeamInvitationController;
use App\Http\Controllers\Finance\FinanceAccountController;
use Freesgen\Atmosphere\Http\Controllers\SettingsController;
use App\Http\Controllers\Relationship\RelationshipController;
use App\Http\Controllers\Finance\FinanceTransactionController;
use App\Domains\Integration\Http\Controllers\ApiIntegrationController;

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

Route::resource('onboarding', OnboardingController::class)->middleware(['auth:sanctum', 'atmosphere.unteamed', 'verified']);

// Automation Services
Route::get('/services/accept-oauth', [ServiceController::class, 'acceptOauth']);

Route::group([], app_path('/Domains/Automation/routes.php'));
Route::group([], app_path('/Domains/Housing/routes.php'));
Route::group([], app_path('/Domains/LogerProfile/routes.php'));
Route::group([], app_path('/Domains/Transaction/routes.php'));
Route::group([], app_path('/Domains/Budget/routes.php'));

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::get('/', fn () => redirect('/dashboard'));

    /**
     *  Jetstream & Settings Section
     */

    // Jetstream teams invitations override
    Route::put('/team-invitations/{invitation}', [TeamInvitationController::class, 'resend'])->name('team-invitations.resend');

    // Settings routes
    Route::controller(SettingsController::class)->group(function () {
        Route::resource('/settings', SettingsController::class);
        Route::get('/settings/tab/{tabName}', 'index');
        Route::get('/settings/{name}', 'section');
    });

    Route::controller(NotificationController::class)->group(function () {
        Route::get('/notifications', 'index')->name('notifications');
        Route::put('/notifications/{notificationId}', 'update')->name('notifications.update');
        Route::patch('/notifications', 'bulkUpdate')->name('notifications.bulk-update');
    });

    /**************************************************************************************
     *                                  Dashboard Section
    ***************************************************************************************/

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/integrations', IntegrationController::class)->name('settings.integrations');
    Route::post('/integrations/google', [IntegrationController::class, 'google'])->name('services.google');

    /**************************************************************************************
     *                                  Meal Section
    ***************************************************************************************/

    //  Meal related routes
    Route::get('/meals/overview', MealController::class)->name('meals.overview');
    Route::controller(MealController::class)->group(function () {
        Route::get('/meals/shopping-list', 'shoppingList')->name('meals.shoppingList');
        Route::resource('/meals', MealController::class);
        Route::post('/meals/add-plan', 'addPlan')->name('meals.addPlan');
        Route::get('/meals-random', 'random')->name('meals.random');
        Route::post('/meals/{meal}/shopping-list', 'addToShoppingList')->name('meals.shoppingList.add');
    });

    Route::controller(IngredientController::class)->group(function () {
        Route::resource('/ingredients', IngredientController::class);
    });

    Route::resource('/meal-planner', MealPlannerController::class);

    /**************************************************************************************
      *                               Finance Section
     ***************************************************************************************/
    // Finance dashboard related routes
    Route::controller(FinanceController::class)->group(function () {
        Route::get('/finance', 'index')->name('finance.overview');
    });

    // Accounts
    Route::resource('/finance/accounts', FinanceAccountController::class, [
        'as' => 'finance',
    ]);

    Route::resource('/finance/lines', FinanceLinesController::class, [
        'as' => 'finance',
    ]);

    // Transactions
    Route::controller(FinanceTransactionController::class)->group(function () {
        Route::post('/linked-drafts', 'findLinkedDrafts')->name('finance.transactions.linked-drafts');
        Route::patch('/transactions/{transaction}/linked', 'findLinked')->name('finance.transactions.linked');
        Route::get('/api/finance/transactions', 'list')->name('finance.transactions.list');
        Route::get('/finance/transactions', 'index')->name('finance.transactions');
        Route::post('/finance/transactions/bulk/delete', 'bulkDelete')->name('finance.transactions.bulk-delete');
        Route::get('/finance/transactions/{state}', 'getByState')->name('finance.transactions.states');
        Route::post('/finance/import', 'import')->name('finance.import');
        Route::get('/finance/export', 'export')->name('finance.export');
        Route::patch('/finance/transactions/{id}/mark-as-paid', 'markPlannedAsPaid')->name('transactions.mark-as-paid');
        Route::post('/finance/transactions', 'addPlanned')->name('transactions.store-planned');
    });

    // Trends
    Route::get('/trends', [FinanceTrendController::class, 'index'])->name('finance.trends');
    Route::get('/trends/{name}', [FinanceTrendController::class, 'index'])->name('finance.trend-section');

    /**************************************************************************************
     *                               Extras Section
    ***************************************************************************************/

    Route::post('/users/{user}/devices', [UserDeviceController::class, 'store']);
    Route::get('/users/{user}/devices', [UserDeviceController::class, 'index']);
    Route::get('/relationships', RelationshipController::class);
    // Automation Services
    Route::post('/services/google', [ServiceController::class, 'google']);
    Route::get('/services/messages', [ServiceController::class, 'getMessages']);
});

/**************************************************************************************
 *                                  API Section
 ***************************************************************************************/

Route::middleware(['auth:sanctum'])->prefix('/api')->name('api.')->group(function () {
    //timezones
    Route::get('/timezones', [TimezonesApiController::class, 'index']);
    // currencies
    Route::get('/currencies', [CurrencyApiController::class, 'index']);
    Route::get('/category-transactions/{category}', [FinanceLinesController::class, 'getTransactions']);
    Route::get('/category-transactions/{category}/details', [FinanceLinesController::class, 'getDetails']);

    // Jetstream teams rewrite
    Route::patch('/v2/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])->name('team-invitations.accept-internal');
    Route::delete('/v2/team-invitations/{invitation}', [TeamInvitationController::class, 'reject'])->name('team-invitations.reject');
});

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->prefix('/api')->name('api.')->group(function () {

    Route::apiResource('integrations', ApiIntegrationController::class);

    Route::get('/services/messages', [ServiceController::class, 'getMessages']);

    //  accounts and transactions
    Route::resource('payees', PayeeApiController::class);
    Route::patch('/accounts', [AccountApiController::class,  'bulkUpdate']);
    Route::resource('categories', CategoryApiController::class);
    Route::patch('/categories', [CategoryApiController::class,  'bulkUpdate']);

    //  recipes & ingredients
    Route::resource('recipes', RecipeApiController::class);
    Route::controller(IngredientApiController::class)->group(function () {
        Route::resource('ingredients', IngredientApiController::class);
        Route::post('/ingredients/{id}/labels', 'addLabel')->name('ingredients.label.add');
    });

    // Labels
    Route::resource('labels', LabelApiController::class);
});
