<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Atmosphere\Http\InertiaController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Insane\Journal\Helpers\CategoryHelper;
use Laravel\Jetstream\Jetstream;
use Atmosphere\Http\Querify;

class FinancialController extends InertiaController {
    use Querify;

    public function __construct(Transaction $transaction)
    {
        $this->model = $transaction;
    }
    public function index(Request $request) {
        $queryParams = $request->query();
        $startDate = $request->query('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $lastMonthStartDate = $request->query('startDate', Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'));
        $lastMonthEndDate = $request->query('endDate', Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'));
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];

        $groupBy = $request->query('group', 'account_id');
        $teamId = $request->user()->current_team_id;
        $groups = [
            'accounts' => 'transactions.account_id, transactions.description',
            'description' => 'transactions.description',
        ];

        $this->modelQuery = Transaction::where([
            'team_id' => $teamId,
            'direction' => "WITHDRAW",
            'status' => 'verified'
        ])->getByMonth($startDate, $endDate, false);

        if ($groupBy && isset($groups[$groupBy])) {

            $this->modelQuery
            ->selectRaw("sum(total) as total, description, group_concat(DISTINCT currency_code) as currency_code")
            ->groupByRaw("$groups[$groupBy], currency_code")
            ->orderByDesc('total');
        }

        $this->getFilters($filters);

        $transactions = $this->modelQuery->get();

        $lastMonthExpenses = Transaction::where([
            'team_id' => $teamId,
            'direction' => "WITHDRAW",
            'status' => 'verified'
        ])->getByMonth($lastMonthStartDate, $lastMonthEndDate)->sum('total');

        return Jetstream::inertia()->render($request, 'Financial/Index', [
            "categories" => CategoryHelper::getSubcategories($teamId, ['expenses', 'incomes']),
            "accounts" => CategoryHelper::getAccounts($teamId, ['cash_and_bank']),
            "transactionTotal" => $transactions->sum('total'),
            "lastMonthExpenses" => $lastMonthExpenses,
            "transactions" => $transactions->map(function ($transaction) use ($groupBy) {
                return Transaction::parser($transaction, (bool) $groupBy);
            }),
            "serverSearchOptions" => [
                "group" => $groupBy,
                "filters" => $filters,
            ]
        ]);
    }
}
