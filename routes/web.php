<?php

use App\Helpers\BudgetHelper;
use App\Helpers\CategoryHelper;
use App\Http\Controllers\Api\AutomationController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\SettingsController;
use App\Models\Budget;
use App\Models\Planner;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard');
        Route::get('/finance', 'finance')->name('finance');
        Route::get('/integrations', 'integrations')->name('integrations');
        Route::post('/services/google', 'google')->name('services.google');
    });

    Route::controller(MealController::class)->group(function () {
        Route::resource('/meals', MealController::class);
        Route::post('/meals/add-plan','addPlan')->name('meals.addPlan');
        Route::get('/meals-random', 'random')->name('meals.random');
    });

    Route::resource('/budgets', BudgetController::class);
    Route::controller(BudgetController::class)->group(function () {
        Route::post('/budgets/planed-budged', 'addPlannedTransaction')->name("budget.planned-transaction");
        Route::put('/transactions/{id}/mark-as-paid', 'markAsPaid')->name("transactions.mark-as-paid");
    });
    Route::resource('/meal-planner', PlannerController::class);

    Route::controller(SettingsController::class)->group(function () {
        Route::resource('/settings', SettingsController::class);
        Route::get('/settings/tab/{tabName}', 'index');
        Route::get('/settings/{name}',  'section');
    });
});

Route::middleware(['auth:sanctum', 'verified'])->prefix('/api')->group(function () {
    Route::resource('categories', CategoryController::class);

    Route::controller(AutomationController::class)->group(function () {
        Route::apiResource('automation', AutomationController::class);
        Route::post('automation/{id}/run', 'run');
    });
});
