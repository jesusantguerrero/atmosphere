<?php

namespace App\Domains\Transaction\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Imports\TransactionsImport;
use App\Domains\Transaction\Models\Transaction;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionService {
    const transactionsTotalSum = "ABS(sum(CASE
    WHEN transactions.direction = 'WITHDRAW'
    THEN transactions.total * -1
    ELSE transactions.total * 1 END)) as total";

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

    public static function getExpenses($teamId, $startDate, $endDate) {
        return Transaction::expenses()
        ->verified()
        ->byTeam($teamId)
        ->inDateFrame($startDate, $endDate)
        ->groupBy(['transactions.id', 'currency_code'])
        ->get();
    }

    public static function getExpensesTotal($teamId, $startDate, $endDate) {
        return Transaction::byTeam($teamId)
        ->balance()
        ->whereNot('categories.name', Category::READY_TO_ASSIGN)
        ->join('categories', 'categories.id', 'transactions.category_id')
        ->inDateFrame($startDate, $endDate)
        ->first();
    }

    public static function getCategoryExpenses($teamId, $startDate, $endDate, $limit = null, $parentId = null) {
        $builder = Transaction::where([
            'transactions.team_id' => $teamId,
            'transactions.status' => 'verified'
        ])
        ->whereNotNull('category_id')
        ->whereNot('categories.name', Category::READY_TO_ASSIGN)
        ->getByMonth($startDate, $endDate, false);

        if ($parentId) {
            $builder->where("categories.parent_id", $parentId);
        }

        return $builder->groupBy('category_id')
        ->select(DB::raw("ABS(sum(CASE
        WHEN transactions.direction = 'WITHDRAW'
        THEN total * -1
        ELSE total * 1 END)) as total,
        category_id, categories.name, categories.parent_id, group.name as parent_name"))
        ->join('categories', 'categories.id', 'category_id')
        ->leftJoin('categories as group', 'group.id', 'categories.parent_id')
        ->orderBy('total', 'desc')
        ->limit($limit)
        ->get();
    }

    public static function getCategoryExpensesGroup($teamId, $startDate, $endDate, $limit = null) {
        $builder = Transaction::where([
            'transactions.team_id' => $teamId,
            'transactions.status' => 'verified'
        ])
        ->whereNotNull('category_id')
        ->whereNot('catGroup.name', Category::INFLOW)
        ->getByMonth($startDate, $endDate, false);

        return $builder
        ->select(DB::raw("ABS(sum(CASE
        WHEN transactions.direction = 'WITHDRAW'
        THEN total * -1
        ELSE total * 1 END)) as total, catGroup.name, catGroup.id"))
        ->join('categories', 'categories.id', 'category_id')
        ->join(DB::raw('categories catGroup'),  'catGroup.id', 'categories.parent_id')
        ->orderBy('total', 'desc')
        ->groupBy('categories.parent_id')
        ->limit($limit)
        ->get();
    }

    public static function getIncome($teamId, $startDate, $endDate) {
        return Transaction::byTeam($teamId)
        ->verified()
        ->byCategories(['inflow'], $teamId)
        ->getByMonth($startDate, $endDate)
        ->sum('total');
    }

    public static function importAndSave($user, $file) {
        $importedData = (new TransactionsImport($user))->toCollection($file);

        foreach ($importedData[0]->toArray() as $transaction) {
            Transaction::createTransaction($transaction);
        }

        return (object) [
            "total" => count($importedData),
            "startDate" => $importedData[0]->min('date'),
            "endDate" => $importedData[0]->max('date')
        ];
    }

    // Trends
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
        with data (month_date, total, balance_type) AS (
            SELECT LAST_DAY(t.date) as month_date, tl.amount * tl.type, accounts.balance_type
            FROM transaction_lines tl
            inner JOIN transactions t on tl.transaction_id = t.id
            inner JOIN accounts on tl.account_id=accounts.id
            where t.STATUS = 'verified'
            AND t.team_id = :teamId
            AND balance_type IS NOT null
          )
          SELECT month_date,
          sum(sum(CASE WHEN balance_type = 'debit' THEN (total) END)) over (ORDER BY month_date) as assets,
          sum(sum(CASE WHEN balance_type = 'credit' THEN (total) END)) over (ORDER BY month_date) as debts
          FROM DATA
          GROUP BY month_date
          ORDER BY month_date
          LIMIT 12;
        ",[
            'teamId' => $teamId
        ]);
    }

    public static function getIncomeVsExpenses($teamId, $timeUnitDiff = 2, $timeUnit = 'month', $type = 'expenses') {
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $startDate = Carbon::now()->subMonth($timeUnitDiff)->startOfMonth()->format('Y-m-d');

        $expenses = self::getInPeriod($teamId, $startDate, $endDate);
        $expensesGroup = $expenses->groupBy('date');
        $expensesCategories = $expenses->unique('id');
        $expensesCategoriesGroup = $expenses->sortBy('index_field')->mapToGroups(function($item) {
            return ["{$item->group_name}:{$item->name}" => $item];
        });

        $income = self::getIncomeByPayeeInPeriod($teamId, $startDate, $endDate);
        $incomeCategories = $income->unique('id')->sortBy('index_field')->values();
        $incomeCategoriesGroup = $income->sortBy('index_field')->mapToGroups(function($item) {
            return [$item->name => $item];
        });

        $dates = $expensesGroup->keys();
        $datesCount = count($dates);

        return
        [
            "dateUnits" => $dates,
            "incomeCategories" => $incomeCategories,
            "incomes" => $incomeCategoriesGroup->map(function ($monthItems) use ($datesCount) {
                $total = $monthItems->sum('total');
                return array_merge([
                    'id' => $monthItems->first()->id,
                    'name' => $monthItems->first()->name,
                    'avg' => Money::of($total, 'USD', null, RoundingMode::HALF_EVEN)
                    ->dividedBy($datesCount, RoundingMode::HALF_EVEN)->getAmount(),
                    'total' => Money::of($total, 'USD', null, RoundingMode::HALF_EVEN)->getAmount()
                    ],
                    $monthItems->mapWithKeys(function($item) {
                        return [$item->date => $item->total];
                    })->toArray(),
                );
            }),
            "expenseCategories" => $expensesCategories->groupBy('group_name'),
            "expenses"=> $expensesCategoriesGroup->map(function ($monthItems) use ($datesCount) {
                $total = $monthItems->sum('total');
                return array_merge([
                    'id' => $monthItems->first()->id,
                    'group_id' => $monthItems->first()->group_id,
                    'name' => $monthItems->first()->name,
                    'group_name' => $monthItems->first()->group_name,
                    'index_field' => $monthItems->first()->index_field,
                    'avg' => Money::of($total, 'USD', null, RoundingMode::HALF_EVEN)
                    ->dividedBy($datesCount, RoundingMode::HALF_EVEN)->getAmount(),
                    'total' => Money::of($total, 'USD', null, RoundingMode::HALF_EVEN)->getAmount()
                    ],
                    $monthItems->mapWithKeys(function($item) {
                        return [$item->date => $item->total];
                    })->toArray(),
                );
            })
        ];
    }

    public static function getInPeriod($teamId, $startDate, $endDate) {
        return DB::table('categories')
        ->selectRaw('sum(COALESCE(total,0)) as total, date_format(transactions.date, "%Y-%m-01") as date, categories.name, categories.id, pc.id group_id, pc.name group_name, concat(pc.index, ".", categories.index) index_field')
        ->where([
            'categories.team_id' => $teamId,
            'categories.resource_type' => 'transactions',
            'transactions.direction' => Transaction::DIRECTION_CREDIT,
            'transactions.status' => 'verified'
        ])->whereNotNull('categories.parent_id')
        ->where('pc.display_id', '!=', 'inflow')
        ->whereBetween('transactions.date', [$startDate, $endDate])
        ->groupByRaw('categories.id, date_format(transactions.date, "%Y-%m-01")')
        ->orderByRaw('date_format(transactions.date, "%Y-%m-01"), concat(pc.index,"." ,categories.index)')
        ->leftJoin('transactions', 'transactions.category_id', '=', 'categories.id')
        ->join(DB::raw('categories pc'), 'pc.id', '=', 'categories.parent_id')
        ->get();
    }

    public static function getIncomeByPayeeInPeriod($teamId, $startDate, $endDate) {
        return DB::table('payees')
        ->selectRaw('sum(COALESCE(total,0)) as total, date_format(transactions.date, "%Y-%m-01") as date, payees.name, payees.id')
        ->where([
            'payees.team_id' => $teamId,
            'transactions.direction' => Transaction::DIRECTION_DEBIT,
            'transactions.status' => 'verified'
        ])
        ->whereBetween('transactions.date', [$startDate, $endDate])
        ->groupByRaw('payees.id, date_format(transactions.date, "%Y-%m-01")')
        ->orderByRaw('date_format(transactions.date, "%Y-%m-01"), payees.name')
        ->join('transactions', 'transactions.payee_id', '=', 'payees.id')
        ->get();
    }
}
