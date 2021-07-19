<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\MealPlanController;
use App\Models\Budget;
use App\Models\MealPlan;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Insane\Journal\Category;
use Insane\Journal\Transaction;

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
    Route::get('/dashboard', function (Request $request) {
        $teamId = $request->user()->current_team_id;

        $budget = Budget::where([
            'team_id' => $teamId
        ])->with('account')->get();

        $transactions = Transaction::getByMonthMacro($teamId, date('m'), date('Y'))->get()
        ;

        return Inertia::render('Dashboard', [
            "meals" => MealPlan::where([
                'team_id' => $teamId,
                'date' => date('Y-m-d')
            ])->with('dateable')->get(),
            "budgetTotal" => $budget->sum('amount'),
            "budget" => $budget,
            "categories" => Category::where([
                'depth' => 0,
                'display_id' => 'expenses'
            ])->with([
                'subCategories',
                'subcategories.accounts' => function ($query) use ($teamId) {
                    $query->where('team_id', '=', $teamId);
                }
            ])->get(),
            "accounts" => Category::where([
                'depth' => 1,
                'display_id' => 'cash_and_bank'
            ])->with([
                'accounts' => function ($query) use ($teamId) {
                    $query->where('team_id', '=', $teamId);
                }
            ])->get(),
            "transactionTotal" => $transactions->sum('total'),
            "transactions" => $transactions->map(function ($transaction) {
                return Transaction::parser($transaction);
            }),
        ]);
    })->name('dashboard');

    Route::get('/finance', function () {
        return Inertia::render('Finance');
    })->name('finance');

    Route::resource('/meals', MealController::class);
    Route::post('/meals/add-plan', [MealController::class, 'addPlan'])->name('meals.addPlan');
    Route::get('/meals-random', [MealController::class, 'random'])->name('meals.random');
    Route::resource('/meal-planner', MealPlanController::class);
    Route::resource('/budgets', BudgetController::class);
});

Route::middleware(['auth:sanctum', 'verified'])->prefix('/api')->group(function () {
    Route::resource('categories', CategoryController::class);
});
