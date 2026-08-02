<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Services\BudgetMonthService;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\PlannedTransactionService;
use App\Domains\Transaction\Services\TransactionService;
use App\Models\Setting;
use Carbon\Carbon;
use Freesgen\Atmosphere\Http\InertiaController;
use Freesgen\Atmosphere\Http\Querify;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;

class FinanceController extends InertiaController
{
    use Querify;

    const DateFormat = 'Y-m-d';

    public function __construct(Transaction $transaction, private TransactionService $service, private PlannedTransactionService $plannedService)
    {
        $this->model = $transaction;

    }

    public function index(Request $request)
    {
        $teamId = $request->user()->current_team_id;
        $settings = Setting::getByTeam(auth()->user()->current_team_id);
        $timeZone = $settings['team_timezone'] ?? config('app.timezone');

        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters, $timeZone);

        $lastMonthStartDate = Carbon::createFromFormat(self::DateFormat, $startDate)->subMonth()->startOfMonth()->format(self::DateFormat);
        $lastMonthEndDate = Carbon::createFromFormat(self::DateFormat, $startDate)->subMonth()->endOfMonth()->format(self::DateFormat);

        $budgetTotal = BudgetMonth::getMonthAssignmentTotal($teamId, $startDate);

        $transactions = TransactionService::getExpenses($teamId, $startDate, $endDate);
        $transactionsTotal = TransactionService::getExpensesTotalByTarget($teamId, $startDate, $endDate);
        $expensesByCategory = TransactionService::getCategoryExpenses($teamId, $startDate, $endDate, 4);
        $expensesByCategoryGroup = TransactionService::getCategoryExpensesGroup($teamId, $startDate, $endDate);
        $lastMonthExpenses = TransactionService::getExpensesTotal($teamId, $lastMonthStartDate, $lastMonthEndDate);
        $income = TransactionService::getIncome($teamId, $startDate, $endDate);
        $lastMonthIncome = TransactionService::getIncome($teamId, $lastMonthStartDate, $lastMonthEndDate);
        $savings = BudgetMonthService::getSavingsBalance($teamId, $endDate);
        $savingsInMonth = BudgetMonthService::getSavingsBalance($teamId, $endDate, $startDate);

        // BudgetCalculated::dispatch();

        return Jetstream::inertia()->render($request, 'Finance/Index', [
            'sectionTitle' => 'Finance',
            'planned' => $this->plannedService->getPlanned($teamId),
            'budgetTotal' => $budgetTotal,
            'expensesByCategory' => $expensesByCategory,
            'expensesByCategoryGroup' => $expensesByCategoryGroup,
            // SUM() over a period with no rows is NULL, not 0 — unlike getIncome(),
            // which uses ->sum() and already returns 0. That asymmetry is what fed
            // the frontend a null baseline and rendered "vs Jul: Infinity%".
            'transactionTotal' => (float) ($transactionsTotal?->total_amount ?? 0),
            'lastMonthExpenses' => (float) ($lastMonthExpenses?->total_amount ?? 0),
            'income' => $income,
            'savings' => $savings,
            'savingsInMonth' => $savingsInMonth,
            'lastMonthIncome' => $lastMonthIncome,
            'transactions' => $transactions->map(function ($transaction) {
                return Transaction::parser($transaction);
            })->take(4),
            'serverSearchOptions' => [
                'filters' => $filters,
            ],
        ]);
    }
}
