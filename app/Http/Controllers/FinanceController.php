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

    private Function getFilterDates($filters) {
        $dates = isset($filters['date']) ? explode("~", $filters['date']) : [
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d')
        ];
        return $dates;
    }

    public function transactions(Request $request, $accountId = null) {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        $groupBy = $request->query('group');
        $teamId = $request->user()->current_team_id;
        $groups = [
            'accounts' => 'transactions.account_id, transactions.description',
            'description' => 'transactions.description',
        ];

        $this->modelQuery = Transaction::where([
            'team_id' => $teamId,
            'status' => 'verified'
        ])->getByMonth($startDate, $endDate, !isset($groups[$groupBy]));

        if ($groupBy && isset($groups[$groupBy])) {
            $this->modelQuery
            ->selectRaw("sum(total) as total, description, group_concat(DISTINCT currency_code) as currency_code")
            ->groupByRaw("$groups[$groupBy], currency_code")
            ->orderByDesc('total');
        }


        if ($accountId) {
            $this->modelQuery->where('account_id', $accountId);
        }

        $this->getFilters($filters);

        $transactions = $this->modelQuery->get();

        return Jetstream::inertia()->render($request, 'Finance/Transactions', [
            "sectionTitle" => "Finance Transactions",
            "categories" => CategoryHelper::getSubcategories($teamId, ['expenses', 'incomes']),
            "transactionTotal" => $transactions->sum('total'),
            "transactions" => $transactions->map(function ($transaction) use ($groupBy) {
                return Transaction::parser($transaction, (bool) $groupBy);
            }),
            "accountId" => $accountId,
            "accounts" => Account::getByDetailTypes($teamId),
            "serverSearchOptions" => [
                "group" => $groupBy,
                "filters" => $filters,
            ]
        ]);
    }

    public function importTransactions(Request $request) {
        Excel::import(new TransactionsImport($request->user()), $request->file('file'));
        return redirect()->back();
    }

    public function importMonthBudgets(Request $request) {
        Excel::import(new BudgetImport($request->user()), $request->file('file'));
        return redirect()->back();
    }

    public function trends(Request $request) {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        $teamId = $request->user()->current_team_id;

        $expensesByCategory = Transaction::getCategoryExpenses($teamId, $startDate, $endDate, 4);
        $expensesByCategoryGroup = Transaction::getCategoryExpensesGroup($teamId, $startDate, $endDate);

        return Jetstream::inertia()->render($request, 'Finance/Trends', [
            "expensesByCategory" => $expensesByCategory,
            "expensesByCategoryGroup" => $expensesByCategoryGroup,
        ]);
    }
}
