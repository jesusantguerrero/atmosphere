<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;
use Freesgen\Atmosphere\Http\InertiaController;
use Freesgen\Atmosphere\Http\Querify;

class FinanceController extends InertiaController {
    use Querify;
    const DateFormat = 'Y-m-d';


    public function __construct(Transaction $transaction)
    {
        $this->model = $transaction;

    }

    public function index(Request $request) {
        $teamId = $request->user()->current_team_id;
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        $lastMonthStartDate = Carbon::createFromFormat(self::DateFormat, $startDate)->subMonth()->startOfMonth()->format(self::DateFormat);
        $lastMonthEndDate = Carbon::createFromFormat(self::DateFormat, $startDate)->subMonth()->endOfMonth()->format(self::DateFormat);

        $budgetTotal = BudgetMonth::getMonthAssignmentTotal($teamId, $startDate);

        $planned = (new TransactionService())->getPlanned($teamId);

        $transactions = TransactionService::getExpenses($teamId, $startDate, $endDate);
        $transactionsTotal = TransactionService::getExpensesTotal($teamId, $startDate, $endDate);
        $expensesByCategory = TransactionService::getCategoryExpenses($teamId, $startDate, $endDate, 4);
        $expensesByCategoryGroup = TransactionService::getCategoryExpensesGroup($teamId, $startDate, $endDate);
        $lastMonthExpenses = TransactionService::getExpensesTotal($teamId, $lastMonthStartDate, $lastMonthEndDate);
        $income = TransactionService::getIncome( $teamId, $startDate, $endDate);
        $lastMonthIncome = TransactionService::getIncome( $teamId, $lastMonthStartDate, $lastMonthEndDate);
        $savings = BudgetCategoryService::getSavings($teamId, $startDate, $endDate);

        return Jetstream::inertia()->render($request, 'Finance/Index', [
            "sectionTitle" => "Finance",
            "planned" => $planned,
            "budgetTotal" => $budgetTotal,
            "expensesByCategory" => $expensesByCategory,
            "expensesByCategoryGroup" => $expensesByCategoryGroup,
            "transactionTotal" => $transactionsTotal->total,
            "lastMonthExpenses" => $lastMonthExpenses->total,
            "income" => $income,
            "savings" => $savings,
            "lastMonthIncome" => $lastMonthIncome,
            "transactions" => $transactions->map(function ($transaction) {
                return Transaction::parser($transaction);
            })->take(4),
        ]);
    }
}