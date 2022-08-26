<?php

namespace App\Http\Controllers;

use App\Domains\Transaction\Services\TransactionService;
use App\Helpers\BudgetHelper;
use App\Models\BudgetMonth;
use App\Models\Transaction;
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
        $lastMonthEndDate = Carbon::createFromFormat(self::DateFormat, $endDate)->subMonth()->endOfMonth()->format(self::DateFormat);

        $budgetTotal = BudgetMonth::getMonthAssignmentTotal($teamId, $startDate);

        $planned = (new TransactionService())->getPlanned($teamId);

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
