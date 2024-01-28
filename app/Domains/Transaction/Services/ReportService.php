<?php

namespace App\Domains\Transaction\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;

class ReportService
{
    public function revenueReport($teamId, $methodName = 'payments')
    {
        $year = Carbon::now()->format('Y');
        $previousYear = Carbon::now()->subYear(1)->format('Y');

        $types = [
            'payments' => 'getPaymentsByYear',
            'expenses' => 'getExpensesByYear',
        ];

        $method = $types[$methodName];

        $results = self::$method($year, $teamId);
        $previousYearResult = self::$method($previousYear, $teamId);

        $results = [
            'currentYear' => [
                'year' => $year,
                'values' => DateMapperService::mapInMonths($results->toArray(), $year),
                'total' => $results->sum('total'),
            ],
            'previousYear' => [
                'year' => $previousYear,
                'values' => DateMapperService::mapInMonths($previousYearResult->toArray(), $previousYear),
                'total' => $previousYearResult->sum('total'),
            ],
        ];

        return $results;
    }

    public static function generateExpensesByPeriod($teamId, $startDate, $timeUnitDiff = 2,  $timeUnit = 'month', $categories = null)
    {
        $rangeEndAt = Carbon::createFromFormat('Y-m-d', $startDate)->endOfMonth()->format('Y-m-d');
        $rangeStartAt = Carbon::now()->subMonth($timeUnitDiff)->startOfMonth()->format('Y-m-d');

        $results = self::getExpensesByCategoriesInPeriod($teamId, $rangeStartAt, $rangeEndAt, $categories);
        $resultGroup = $results->groupBy('date');

        return $resultGroup->map(function ($monthItems) {
            return [
                'date' => $monthItems->first()->date,
                'data' => $monthItems->sortByDesc('total_amount')->values(),
                'total' => $monthItems->sum('total_amount'),
            ];
        }, $resultGroup)->sortBy('date');
    }

    public static function getIncomeVsExpenses($teamId, $timeUnitDiff = 2, $startDate = null, $timeUnit = 'month')
    {
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $startDate = Carbon::now()->subMonth($timeUnitDiff)->startOfMonth()->format('Y-m-d');

        if ($timeUnit == 'year') {
            $endDate = Carbon::now()->endOfYear()->format('Y-m-d');
            $startDate = Carbon::now()->subYear($timeUnitDiff)->startOfYear()->format('Y-m-d');
        }

        $expenses = self::getExpensesByCategoriesInPeriod($teamId, $startDate, $endDate);
        $expensesGroup = $expenses->groupBy($timeUnit);



        $income = self::getIncomeByPayeeInPeriod($teamId, $startDate, $endDate);
        $incomeCategories =  $income->groupBy($timeUnit);

        $dates = $expensesGroup->keys();


        return $dates->map(function ($dateUnit) use ($incomeCategories, $expensesGroup) {
            $incomeData = $incomeCategories->get($dateUnit);
            $expenseData = $expensesGroup->get($dateUnit);
            return [
                'date' => $dateUnit,
                'date_unit' => $dateUnit,
                'income' => $incomeData?->values()->all() ?? [],
                "expense" => $expenseData?->values()->all() ?? [],
                'assets' => $incomeData?->sum('total_amount') ?? 0,
                'debts' => $expenseData?->sum('total') ?? 0,
            ];
        })->sortByDesc($timeUnit)->values()->toArray();
    }

    public static function generateCurrentPreviousReport($teamId, $timeUnit = 'month', $timeUnitDiff = 2, $type = 'expenses')
    {
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $startDate = Carbon::now()->subMonth($timeUnitDiff)->startOfMonth()->format('Y-m-d');

        $results = self::getExpensesInPeriod($teamId, $startDate, $endDate);
        $resultGroup = $results->groupBy('month');

        return $resultGroup->map(function ($monthItems) {
            [$year, $month] = explode('-', $monthItems->first()->month);


            return [
                'date' => $monthItems->first()->date,
                'data' => DateMapperService::mapInDays($monthItems->groupBy('date')->toArray(), $month, $year),
                'total' => $monthItems->sum('total_amount'),
            ];
        }, $resultGroup)->sortBy('date');
    }

    public static function getPaymentsByYear($year, $teamId)
    {
        return DB::table('payments')
            ->where(DB::raw('YEAR(payments.payment_date)'), '=', $year)
            ->where('team_id', '=', $teamId)
            ->selectRaw('sum(COALESCE(amount,0)) as total, YEAR(payments.payment_date) as year, MONTH(payments.payment_date) as months')
            ->groupByRaw('MONTH(payments.payment_date), YEAR(payments.payment_date)')
            ->get();
    }

    public static function getExpensesByYear($year, $teamId)
    {
        return DB::table('transactions')
            ->where(DB::raw('YEAR(transactions.date)'), '=', $year)
            ->where([
                'team_id' => $teamId,
                'direction' => Transaction::DIRECTION_CREDIT,
                'status' => 'verified',
            ])
            ->whereNotNull('category_id')
            ->selectRaw('sum(COALESCE(total,0)) as total, YEAR(transactions.date) as year, MONTH(transactions.date) as months')
            ->groupByRaw('MONTH(transactions.date), YEAR(transactions.date)')
            ->get();
    }

    public static function getIncomeByPayeeInPeriod($teamId, $startDate, $endDate)
    {
        return TransactionLine::byTeam($teamId)
            ->balance()
            ->inDateFrame($startDate, $endDate)
            ->incomePayees()
            ->selectRaw('date_format(transaction_lines.date, "%Y-%m-%01") as date, date_format(transaction_lines.date, "%Y-%m-%01") as month, year(transaction_lines.date) as year, payees.name, payees.id')
            ->groupByRaw('date_format(transaction_lines.date, "%Y-%m"), payees.id')
            ->orderByDesc('date')
            ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
            ->get();
    }

    public static function getExpensesByCategoriesInPeriod($teamId, $startDate, $endDate, $categories = null)
    {
        return TransactionLine::byTeam($teamId)
            ->balance()
            ->inDateFrame($startDate, $endDate)
            ->expenseCategories($categories)
            ->selectRaw("
                date_format(transaction_lines.date,
                '%Y-%m-01') as date,
                date_format(transaction_lines.date, '%Y-%m-01') as month,
                year(transaction_lines.date) as year,
                categories.name,
                categories.id,
                budget_targets.target_type
                "
            )
            ->groupByRaw('date_format(transaction_lines.date, "%Y-%m"), categories.id')
            ->orderBy('date')
            ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
            ->join('budget_targets', 'budget_targets.category_id', 'categories.id')
            ->get();
    }

    public static function getExpensesInPeriod($teamId, $startDate, $endDate)
    {
        return TransactionLine::byTeam($teamId)
            ->balance()
            ->inDateFrame($startDate, $endDate)
            ->expenseCategories()
            ->selectRaw('date_format(transaction_lines.date, "%Y-%m-%d") as date, year(transaction_lines.date) as year, date_format(transaction_lines.date, "%Y-%m") as month')
            ->groupByRaw('date_format(transaction_lines.date, "%Y-%m"), transaction_lines.date')
            ->orderByDesc('date')
            ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
            ->get();
    }

    public function getAccountBalance($accountId)
    {
        return DB::table('transaction_lines')
            ->where([
                'account_id' => $accountId,
            ])
            ->selectRaw('sum(amount * type)  as total')
            ->get();
    }

    public function getAccountStats($accountId, $startDate, $endDate)
    {
        return DB::table('transaction_lines')
            ->where('account_id', $accountId)
            ->whereBetween('date', [$startDate, $endDate])->select(DB::raw('
        sum(amount * type) total,
        sum(
            CASE
            WHEN type = -1 THEN amount
            ELSE 0
        END) credit,
        sum(
            CASE
            WHEN type = 1 THEN amount
            ELSE 0
        END) debit'))->first();
    }

    public function yearSummary($teamId, $year)
    {
        $date = Carbon::createFromFormat('Y', $year);
        $startDate = $date->startOfYear()->format('Y-m-d');
        $endDate = $date->endOfYear()->format('Y-m-d');

        $qIncome = DB::table('transactions')
            ->where(DB::raw('YEAR(transactions.date)'), '=', $year)
            ->where([
                'transactions.team_id' => $teamId,
                'direction' => Transaction::DIRECTION_DEBIT,
                'status' => 'verified',
            ])
            ->whereNotNull('category_id');

        $earned = $qIncome->sum('total');

        $topIncome = $qIncome->orderByDesc('total')
            ->take(4)->get();

        $topPayers = $qIncome->selectRaw('sum(total) total, payees.name payeeName')
            ->groupBy('payee_id')
            ->orderByDesc('total')
            ->join('payees', 'transactions.payee_id', '=', 'payees.id')
            ->take(4)
            ->get();

        $topPayees = TransactionService::getPayeeMovementsInPeriod($teamId, $startDate, $endDate, Transaction::DIRECTION_CREDIT)
            ->take(4)
            ->orderByRaw('total desc')
            ->get();

        $topExpense = DB::table('transactions')
            ->where(DB::raw('YEAR(transactions.date)'), '=', $year)
            ->where([
                'transactions.team_id' => $teamId,
                'direction' => Transaction::DIRECTION_CREDIT,
                'status' => 'verified',
            ])
            ->whereNotNull('category_id')
            ->orderByDesc('total')
            ->take(4)->get();

        $data = [
            'earned' => $earned,
            'earnedAvg' => $earned / 12,
            'topPayers' => $topPayers,
            'topPayees' => $topPayees,
            'topExpense' => $topExpense,
            'topIncome' => $topIncome,
        ];

        return $data;
    }

    public function nextInvoices($teamId)
    {
        return DB::table('invoices')
            ->selectRaw('clients.names contact, clients.id contact_id, invoices.debt, invoices.due_date, invoices.id id, invoices.concept')
            ->where('invoices.team_id', '=', $teamId)
            ->where('invoices.status', '=', 'unpaid')
            ->whereRaw('invoices.due_date >= NOW()')
            ->where('invoices.type', '=', 'INVOICE')
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->take(5)
            ->get();
    }

    public function debtors($teamId)
    {
        return DB::table('invoices')
            ->selectRaw('count(invoices.id) total_debts, sum(invoices.debt) debt, clients.names contact,clients.id contact_id')
            ->where('invoices.team_id', '=', $teamId)
            ->where('invoices.status', '=', 'unpaid')
            ->whereRaw('invoices.due_date <= NOW()')
            ->where('invoices.type', '=', 'INVOICE')
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->take(5)
            ->groupBy('invoices.client_id')
            ->get();
    }
}
