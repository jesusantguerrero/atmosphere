<?php

namespace App\Domains\Transaction\Services;

use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService {
  public function revenueReport($teamId, $methodName = 'payments') {
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
        "currentYear" => [
            "year" => $year,
            "values" => $this->mapInMonths($results->toArray(), $year),
            "total" =>  $results->sum('total')
        ],
        "previousYear"=> [
            "year" => $previousYear,
            "values" => $this->mapInMonths($previousYearResult->toArray(), $previousYear),
            "total" =>  $previousYearResult->sum('total')
        ]
    ];
    return $results;
  }

  public static function generateExpensesByPeriod($teamId, $timeUnit = 'month', $timeUnitDiff = 2 , $type = 'expenses') {
    $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    $startDate = Carbon::now()->subMonth($timeUnitDiff)->startOfMonth()->format('Y-m-d');

    $results = self::getExpensesByPeriod($teamId, $startDate, $endDate);
    $resultGroup = $results->groupBy('date');

    return $resultGroup->map(function ($monthItems) {
      return [
        'date' => $monthItems->first()->date,
        'data' => $monthItems->sortByDesc('total')->values()->all(),
        'total' => $monthItems->sum('total')
      ];
    }, $resultGroup)->sortBy('date');
  }

  public static function getPaymentsByYear($year, $teamId) {
    return DB::table('payments')
    ->where(DB::raw('YEAR(payments.payment_date)'), '=', $year)
    ->where('team_id', '=', $teamId)
    ->selectRaw('sum(COALESCE(amount,0)) as total, YEAR(payments.payment_date) as year, MONTH(payments.payment_date) as months')
    ->groupByRaw('MONTH(payments.payment_date), YEAR(payments.payment_date)')
    ->get();
  }

  public static function getExpensesByYear($year, $teamId) {
    return DB::table('transactions')
    ->where(DB::raw('YEAR(transactions.date)'), '=', $year)
    ->where([
        'team_id' => $teamId,
        'direction' => Transaction::DIRECTION_CREDIT,
        'status' => 'verified'
    ])
    ->whereNotNull('category_id')
    ->selectRaw('sum(COALESCE(total,0)) as total, YEAR(transactions.date) as year, MONTH(transactions.date) as months')
    ->groupByRaw('MONTH(transactions.date), YEAR(transactions.date)')
    ->get();
  }

  public static function getExpensesByPeriod($teamId, $startDate, $endDate) {
    return Transaction::byTeam($teamId)
    ->balance()
    ->inDateFrame($startDate, $endDate)
    ->expenseCategories()
    ->selectRaw('date_format(transactions.date, "%Y-%m-01") as date, categories.name, categories.id')
    ->groupByRaw('date_format(transactions.date, "%Y-%m"), categories.id')
    ->orderBy('date')
    ->get();
  }

  public function getAccountBalance($accountId) {
    return DB::table('transaction_lines')
    ->where([
        'account_id' => $accountId
    ])
    ->selectRaw('sum(amount * type)  as total')
    ->get();
  }

  public function mapInMonths($data, $year) {
    $months = [1, 2, 3,4,5,6,7,8,9,10,11, 12];
    return array_map(function ($month) use ($data, $year) {
        $index = array_search($month, array_column($data, 'months'));

        return  $index !== false ? $data[$index] : [
            "year" => $year,
            "months" => $month,
            "total" =>  0
        ];
    }, $months);
  }


  public function nextInvoices($teamId) {
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

  public function debtors($teamId) {
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
