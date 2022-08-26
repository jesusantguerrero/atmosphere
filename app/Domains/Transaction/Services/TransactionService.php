<?php

namespace App\Domains\Transaction\Services;

use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService {
    function __construct()
    {
        $this->model = new Transaction();
    }

    public function getList($teamId, $options) {
            return $this->model::verified()
            ->where('team_id', $teamId)
            ->inDateFrame($options['startDate'], $options['endDate'])
            ->with(['mainLine', 'lines', 'category', 'mainLine.account', 'category.account'])
            ->get();
    }

    public function getPlanned($teamId) {
        return Transaction::where([
            'team_id' => $teamId,
        ])
        ->planned()
        ->get()
        ->map(function ($transaction) {
            return Transaction::parser($transaction);
        });
    }

    public function getForAccount($accountId, $teamId,  $options) {
        return $this->model::verified()
            ->where('team_id', $teamId)
            ->inDateFrame($options['startDate'], $options['endDate'])
            ->forAccount($accountId)
            ->with(['mainLine', 'lines', 'category', 'mainLine.account', 'category.account'])
            ->get();
    }

    public function getNetWorthMonth($teamId, $endDate) {
        return $this->model::where("transactions.date", "<=", $endDate)
        ->where([
            'transactions.team_id' => $teamId,
            'transactions.status' => 'verified'
        ])
        ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
        ->selectRaw("sum(CASE WHEN transactions.direction = 'WITHDRAW' THEN total * -1 ELSE total * 1 END) as total, accounts.balance_type")
        ->groupBy('accounts.balance_type')
        ->get();
    }

    public static function getNetWorth($teamId, $startDate, $endDate) {
        return DB::select("
        with data (month_date, total, direction, balance_type) AS (
            SELECT LAST_DAY(transactions.date) as month_date, total, direction, accounts.balance_type
            FROM transactions
            inner JOIN accounts on transactions.account_id=accounts.id
            AND transactions.STATUS = 'verified'
            AND transactions.team_id = :teamId
            AND balance_type IS NOT null
          )
          SELECT month_date,
          sum(sum(CASE WHEN balance_type = 'debit' THEN (CASE WHEN direction = 'withdraw' THEN total * -1 ELSE total * 1 END) END)) over (ORDER BY month_date) as assets,
          sum(sum(CASE WHEN balance_type = 'credit' THEN (CASE WHEN direction = 'withdraw' THEN total * -1 ELSE total * 1 END) END)) over (ORDER BY month_date) as debts
          FROM DATA
          GROUP BY month_date
          ORDER BY month_date
          LIMIT 12;
        ",[
            'teamId' => $teamId
        ]);
    }

    public static function getExpensesTotal($teamId, $startDate, $endDate) {
        return Transaction::where([
            'team_id' => $teamId,
            'direction' => Transaction::DIRECTION_CREDIT,
            'status' => 'verified'
        ])
        ->whereNotNull('transaction_category_id')
        ->whereBetween('date', [$startDate, $endDate])
        ->select(DB::raw('SUM(total) as total, currency_code'))
        ->groupBy('currency_code')
        ->get();
    }

    public static function getCategoryExpenses($teamId, $startDate, $endDate, $limit = null, $parentId = null) {
        $builder = Transaction::where([
            'transactions.team_id' => $teamId,
            'direction' => Transaction::DIRECTION_CREDIT,
            'transactions.status' => 'verified'
        ])
        ->whereNotNull('transaction_category_id')
        ->getByMonth($startDate, $endDate, false);

        if ($parentId) {
            $builder->where("categories.parent_id", $parentId);
        }

        return $builder->groupBy('transaction_category_id')
        ->select(DB::raw('sum(total) as total, transaction_category_id, categories.name, categories.parent_id, group.name as parent_name'))
        ->join('categories', 'categories.id', 'transaction_category_id')
        ->leftJoin('categories as group', 'group.id', 'categories.parent_id')
        ->orderBy('total', 'desc')
        ->limit($limit)
        ->get();
    }

    public static function getCategoryExpensesGroup($teamId, $startDate, $endDate, $limit = null) {
        $builder = Transaction::where([
            'transactions.team_id' => $teamId,
            'direction' => Transaction::DIRECTION_CREDIT,
            'transactions.status' => 'verified'
        ])
        ->whereNotNull('transaction_category_id')
        ->getByMonth($startDate, $endDate, false);

        return $builder
        ->select(DB::raw('sum(total) as total, catGroup.name, catGroup.id'))
        ->join('categories', 'categories.id', 'transaction_category_id')
        ->join(DB::raw('categories catGroup'),  'catGroup.id', 'categories.parent_id')
        ->orderBy('total', 'desc')
        ->groupBy('categories.parent_id')
        ->limit($limit)
        ->get();
    }

    public static function getIncome($teamId, $startDate, $endDate) {
        return Transaction::where([
            'team_id' => $teamId,
            'status' => 'verified'
        ])
        ->byCategories(['inflow'], $teamId)
        ->getByMonth($startDate, $endDate)
        ->sum('total');
    }

    public static function getSavings($teamId, $startDate, $endDate) {
        // todo
    }
}
