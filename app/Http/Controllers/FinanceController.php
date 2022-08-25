<?php

namespace App\Http\Controllers;

use App\Helpers\BudgetHelper;
use App\Imports\BudgetImport;
use App\Imports\TransactionsImport;
use App\Models\BudgetMonth;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;
use Insane\Journal\Helpers\CategoryHelper;
use Freesgen\Atmosphere\Http\InertiaController;
use Freesgen\Atmosphere\Http\Querify;
use Insane\Journal\Models\Core\Account;
use Maatwebsite\Excel\Facades\Excel;

class FinanceController extends InertiaController {
    use Querify;
    const DateFormat = 'Y-m-d';


    public function __construct(Transaction $transaction)
    {
        $this->model = $transaction;

    }

    public function index(Request $request) {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        $lastMonthStartDate = Carbon::createFromFormat(self::DateFormat, $startDate)->subMonth()->startOfMonth()->format(self::DateFormat);
        $lastMonthEndDate = Carbon::createFromFormat(self::DateFormat, $endDate)->subMonth()->endOfMonth()->format(self::DateFormat);
        $teamId = $request->user()->current_team_id;

        $budgetTotal = BudgetMonth::getMonthAssignmentTotal($teamId, $startDate);

        $planned = BudgetHelper::getPlannedTransactions($teamId);

        $transactions = Transaction::getExpenses($teamId, $startDate, $endDate);
        $expensesByCategory = Transaction::getCategoryExpenses($teamId, $startDate, $endDate, 4);
        $expensesByCategoryGroup = Transaction::getCategoryExpensesGroup($teamId, $startDate, $endDate);
        $lastMonthExpenses= Transaction::getExpenses($teamId, $lastMonthStartDate, $lastMonthEndDate)->sum('total');
        $income = Transaction::getIncome( $teamId, $startDate, $endDate);
        $lastMonthIncome = Transaction::getIncome( $teamId, $lastMonthStartDate, $lastMonthEndDate);

        return Jetstream::inertia()->render($request, 'Finance/Index', [
            "sectionTitle" => "Finance",
            "planned" => $planned,
            "budgetTotal" => $budgetTotal,
            "expensesByCategory" => $expensesByCategory,
            "expensesByCategoryGroup" => $expensesByCategoryGroup,
            "categories" => CategoryHelper::getSubcategories($teamId, ['expenses', 'incomes']),
            "accounts" => Account::getByDetailTypes($teamId),
            "transactionTotal" => $transactions->sum('total'),
            "lastMonthExpenses" => $lastMonthExpenses,
            "income" => $income,
            "lastMonthIncome" => $lastMonthIncome,
            "transactions" => $transactions->map(function ($transaction) {
                return Transaction::parser($transaction);
            })->take(4),
        ]);
    }
}
