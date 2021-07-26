<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\MealPlanController;
use App\Models\Budget;
use App\Models\MealPlan;
use App\Models\Transaction as ModelsTransaction;
use Carbon\Carbon;
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
        $startDate = $request->query('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $teamId = $request->user()->current_team_id;


        $budget = Budget::where([
            'team_id' => $teamId
        ])->with('account')->get();

        $transactions = Transaction::where([
            'team_id' => $teamId,
            'direction' => "WITHDRAW",
            'status' => 'verified'
        ])->getByMonth($startDate, $endDate)->get();


        return Inertia::render('Dashboard', [
            "meals" => MealPlan::where([
                'team_id' => $teamId,
                'date' => date('Y-m-d')
            ])->with('dateable')->get(),
            "budgetTotal" => $budget->sum('amount'),
            "budget" => $budget->map(function ($budget) use($startDate, $endDate) {
               return Budget::dashboardParser($budget, $startDate, $endDate);
            }),
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

    Route::get('/finance', function (Request $request) {
        $startDate = $request->query('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $lastMonthStartDate = $request->query('startDate', Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'));
        $lastMonthEndDate = $request->query('endDate', Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'));
        $teamId = $request->user()->current_team_id;


        $budget = Budget::where([
            'team_id' => $teamId
        ])->with('account')->get();


        $planned = ModelsTransaction::where([
            'team_id' => $teamId,
            'status' => 'planned'
        ])->get()->map(function ($transaction) {
            return ModelsTransaction::parser($transaction);
        });

        $transactions = Transaction::where([
            'team_id' => $teamId,
            'direction' => "WITHDRAW",
            'status' => 'verified'
        ])->getByMonth($startDate, $endDate)->get();

        $incomes = Transaction::where([
            'team_id' => $teamId,
            'direction' => "DEPOSIT",
            'status' => 'verified'
        ])->getByMonth($startDate, $endDate)->sum('total');

        $lastMonthIncomes= Transaction::where([
            'team_id' => $teamId,
            'direction' => "DEPOSIT",
            'status' => 'verified'
        ])->getByMonth($lastMonthStartDate, $lastMonthEndDate)->sum('total');

        $lastMonthExpenses= Transaction::where([
            'team_id' => $teamId,
            'direction' => "WITHDRAW",
            'status' => 'verified'
        ])->getByMonth($lastMonthStartDate, $lastMonthEndDate)->sum('total');

        return Inertia::render('Finance', [
            "planned" => $planned,
            "budgetTotal" => $budget->sum('amount'),
            "budget" => $budget->map(function ($budget) use($startDate, $endDate) {
               return Budget::dashboardParser($budget, $startDate, $endDate);
            }),
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
            "lastMonthExpenses" => $lastMonthExpenses,
            "income" => $incomes,
            "lastMonthIncome" => $lastMonthIncomes,
            "transactions" => $transactions->map(function ($transaction) {
                return Transaction::parser($transaction);
            })->take(4),
        ]);
    })->name('finance');



    Route::resource('/meals', MealController::class);
    Route::post('/meals/add-plan', [MealController::class, 'addPlan'])->name('meals.addPlan');
    Route::get('/meals-random', [MealController::class, 'random'])->name('meals.random');
    Route::resource('/meal-planner', MealPlanController::class);
    Route::resource('/budgets', BudgetController::class);
    Route::post('/budgets/planed-budged', [BudgetController::class, 'addPlannedTransaction'])->name("budget.planned-transaction");
    Route::put('/transactions/{id}/mark-as-paid', [BudgetController::class, 'markAsPaid'])->name("transactions.mark-as-paid");
});

Route::middleware(['auth:sanctum', 'verified'])->prefix('/api')->group(function () {
    Route::resource('categories', CategoryController::class);
});
