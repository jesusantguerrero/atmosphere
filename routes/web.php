<?php

use App\Helpers\BudgetHelper;
use App\Helpers\CategoryHelper;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\PlannerController;
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
            "strings" => __('dashboard'),
            "meals" => Planner::where([
                'team_id' => $teamId,
                'date' => date('Y-m-d')
            ])->with('dateable')->get(),
            "budgetTotal" => $budget->sum('amount'),
            "budget" => $budget->map(function ($budget) use($startDate, $endDate) {
               return Budget::dashboardParser($budget, $startDate, $endDate);
            }),
            "categories" => $teamId ? CategoryHelper::getSubcategories($teamId, ['expenses', 'incomes']) : null,
            "accounts" => $teamId ? CategoryHelper::getAccounts($teamId, ['cash_and_bank']) : null,
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


        $planned = BudgetHelper::getPlannedTransactions($teamId);
        $subscriptions = BudgetHelper::getPlannedTransactions($teamId, 1);

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
            "subscriptions" => $subscriptions,
            "budgetTotal" => $budget->sum('amount'),
            "budget" => $budget->map(function ($budget) use($startDate, $endDate) {
               return Budget::dashboardParser($budget, $startDate, $endDate);
            }),
            "categories" => CategoryHelper::getSubcategories($teamId, ['expenses', 'incomes']),
            "accounts" => CategoryHelper::getAccounts($teamId, ['cash_and_bank']),
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
    Route::resource('/meal-planner', PlannerController::class);
    Route::resource('/budgets', BudgetController::class);
    Route::post('/budgets/planed-budged', [BudgetController::class, 'addPlannedTransaction'])->name("budget.planned-transaction");
    Route::put('/transactions/{id}/mark-as-paid', [BudgetController::class, 'markAsPaid'])->name("transactions.mark-as-paid");
});

Route::middleware(['auth:sanctum', 'verified'])->prefix('/api')->group(function () {
    Route::resource('categories', CategoryController::class);
});
