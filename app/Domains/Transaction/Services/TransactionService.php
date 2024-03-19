<?php

namespace App\Domains\Transaction\Services;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Transaction\Models\TransactionLine;
use App\Domains\Transaction\Imports\TransactionsImport;
use Insane\Journal\Models\Core\Category as CoreCategory;

class TransactionService
{
    const transactionsTotalSum = "ABS(sum(CASE
    WHEN transactions.direction = 'WITHDRAW'
    THEN transactions.total * -1
    ELSE transactions.total * 1 END)) as total";

    protected $model;

    public function __construct()
    {
        $this->model = new Transaction();
    }

    public function getList($teamId, $options)
    {
        return $this->model::verified()
            ->where('team_id', $teamId)
            ->inDateFrame($options['startDate'], $options['endDate'])
            ->with(['mainLine', 'lines', 'counterLine', 'mainLine.account', 'counterLine.account'])
            ->get();
    }

    public function getListByStatus(string $status)
    {
        return DB::table('transactions')
            ->selectRaw('
                transactions.id,
                transactions.description,
                transactions.date,
                transactions.direction,
                transactions.status,
                transactions.total,
                transactions.account_id,
                transactions.counter_account_id,
                transactions.payee_id,
                categories.name category_name,
                payees.name payee_name,
                ca.name counter_account_name,
                accounts.name account_name,
                linked.id linked_transaction_id,
                linked.total linked_transaction_total,
                transactions.team_id,
                transactions.user_id
            ')
            ->leftJoin('categories', 'categories.id', 'transactions.category_id')
            ->leftJoin('payees', 'payees.id', 'transactions.payee_id')
            ->leftJoin(DB::raw('accounts ca'), 'ca.id', 'transactions.counter_account_id')
            ->leftJoin('accounts', 'accounts.id', 'transactions.account_id')
            ->leftJoin('linked_transactions', 'transactions.id', 'linked_transactions.transaction_id')
            ->leftJoin(DB::raw('transactions linked'), 'linked.id', 'linked_transactions.linked_transaction_id')
            ->where('transactions.status', $status);
    }

    public function getForAccount($accountId, $teamId, $options)
    {
        return $this->model::verified()
            ->where('team_id', $teamId)
            ->inDateFrame($options['startDate'], $options['endDate'])
            ->forAccount($accountId)
            ->with(['mainLine', 'lines', 'counterLine', 'mainLine.account', 'counterLine.account'])
            ->get();
    }

    public static function getExpenses($teamId, $startDate, $endDate)
    {
        return Transaction::expenses()
            ->verified()
            ->byTeam($teamId)
            ->inDateFrame($startDate, $endDate)
            ->groupBy(['transactions.id', 'currency_code'])
            ->get();
    }

    public static function getExpensesTotal($teamId, $startDate, $endDate)
    {
        return TransactionLine::byTeam($teamId)
            ->balance()
            ->inDateFrame($startDate, $endDate)
            ->expenseCategories()
            ->whereNot('categories.name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
            ->first();
    }

    public static function getExpensesTotalByTarget($teamId, $startDate, $endDate)
    {
        return TransactionLine::byTeam($teamId)
            ->balance()
            ->inDateFrame($startDate, $endDate)
            ->addSelect('budget_targets.target_type')
            ->expenseCategories()
            ->whereNot('categories.name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->join('transactions', 'transactions.id', 'transaction_lines.transaction_id')
            ->leftJoin('budget_targets', 'budget_targets.category_id', 'categories.id')
            ->first();
    }

    public static function getCategoryExpenses($teamId, $startDate, $endDate, $limit = null, $parentId = null)
    {
        $DIRECTION_FACTOR = -1;
        $builder = DB::table('transaction_lines')->where([
            'transaction_lines.team_id' => $teamId,
            'transactions.status' => 'verified',
        ])
            ->whereNotNull('transaction_lines.category_id')
            ->whereNot('categories.name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->whereBetween('transactions.date', [$startDate, $endDate]);

        if ($parentId) {
            $builder->where('categories.parent_id', $parentId);
        }

        return $builder->groupBy('transaction_lines.category_id')
            ->selectRaw("sum(transaction_lines.amount * transaction_lines.type * ?) as total,
            transaction_lines.category_id,
            categories.name,
            categories.parent_id,
            group.name as parent_name,
            group_concat(concat(transaction_lines.id, '/', accounts.name, '/', transactions.date, '/', payees.name, '/', transaction_lines.concept, '/', amount * transaction_lines.type) SEPARATOR '|') as details
        ", [
                $DIRECTION_FACTOR,
            ])
            ->join('transactions', 'transactions.id', 'transaction_id')
            ->join('categories', 'categories.id', 'transaction_lines.category_id')
            ->join('accounts', 'accounts.id', 'transaction_lines.account_id')
            ->join('payees', 'payees.id', 'transaction_lines.payee_id')
            ->leftJoin('categories as group', 'group.id', 'categories.parent_id')
            ->orderBy('total', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getCategoryExpenseDetails($teamId, $startDate, $endDate, $limit = null, CoreCategory $category = null, $parentId = null)
    {

        $result = DB::table('transaction_lines')->where([
            'transaction_lines.team_id' => $teamId,
            'transactions.status' => 'verified',
        ])
            ->whereNot('categories.name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->when($category && !$category?->account_id, fn ($q) => $q->where('categories.id', $category->id)->groupBy('transaction_lines.category_id'))
            ->when($category?->account_id, fn ($q) => $q->where('accounts.id', $category->account_id))
            ->when($parentId, fn ($q) => $q->where('group.id', $parentId)->groupBy('group.id'))
            ->selectRaw("
                transaction_lines.category_id,
                categories.name,
                categories.parent_id,
                accounts.id as account_id,
                group.name as parent_name,
                transaction_lines.id,
                accounts.name,
                transactions.date,
                payees.name payee_name,
                transaction_lines.concept,
                amount * transaction_lines.type total
            ")
            ->leftJoin('transactions', 'transactions.id', 'transaction_id')
            ->leftJoin('categories', 'categories.id', 'transaction_lines.category_id')
            ->join('accounts', 'accounts.id', 'transaction_lines.account_id')
            ->leftJoin('payees', 'payees.id', 'transaction_lines.payee_id')
            ->leftJoin('categories as group', 'group.id', 'categories.parent_id')
            ->orderByDesc('transaction_lines.date')
            ->get();


        return [
            "total" => $result->sum('total'),
            "data" => $result
        ];
    }

    public static function getCategoryExpensesGroup($teamId, $startDate, $endDate, $limit = null)
    {
        $totals = DB::table('transaction_lines')->where([
            'transaction_lines.team_id' => $teamId,
            'transactions.status' => 'verified',
        ])
            ->whereNotNull('transaction_lines.category_id')
            ->whereNot('catGroup.name', BudgetReservedNames::INFLOW->value)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->select(DB::raw("
            ABS(sum(transaction_lines.amount * transaction_lines.type)) as total,
            catGroup.name,
            catGroup.id,
            group_concat(concat(transaction_lines.id, '/',accounts.name, '/', transactions.date, '/', payees.name, '/', transaction_lines.concept, '/', amount * transaction_lines.type) SEPARATOR '|') as details
            ",
            ))
            ->join('transactions', 'transactions.id', 'transaction_id')
            ->join('accounts', 'accounts.id', 'transaction_lines.account_id')
            ->join('categories', 'categories.id', 'transaction_lines.category_id')
            ->join('payees', 'payees.id', 'transaction_lines.payee_id')
            ->join(DB::raw('categories catGroup'), 'catGroup.id', 'categories.parent_id')
            ->orderBy('transactions.date', 'desc')
            ->groupBy('categories.parent_id')
            ->limit($limit)
            ->get();

        return $totals->sortByDesc('total')->values()->all();
    }

    public static function getIncome($teamId, $startDate, $endDate)
    {
        return Transaction::byTeam($teamId)
            ->verified()
            ->byCategories(['inflow'], $teamId)
            ->getByMonth($startDate, $endDate)
            ->sum('total');
    }

    public static function importAndSave($user, $filePath)
    {

        (new TransactionsImport($user))->queue($filePath);
        unlink($filePath);
    }

    public static function getBalanceTo($user, $date)
    {

        `FROM transaction_lines tl
        inner JOIN transactions ON transactions.id = tl.transaction_id
        LEFT JOIN accounts acc ON acc.id = tl.account_id
        LEFT JOIN account_detail_types adt ON adt.id = acc.account_detail_type_id
        WHERE transactions.DATE < "2022-06-31" AND transactions.team_id = 2
        AND tl.category_id IS NOT NULL
        AND tl.category_id <> 0
        AND acc.name <> 'credit_card'
        ORDER BY transactions.DATE`;
    }

    // Trends
    public function getNetWorthMonth($teamId, $endDate)
    {
        return $this->model::where('transactions.date', '<=', $endDate)
            ->where([
                'transactions.team_id' => $teamId,
                'transactions.status' => 'verified',
            ])
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->selectRaw("sum(CASE WHEN transactions.direction = 'WITHDRAW' THEN total * -1 ELSE total * 1 END) as total, accounts.balance_type")
            ->groupBy('accounts.balance_type')
            ->get();
    }

    public static function getNetWorth($teamId, $startDate, $endDate)
    {
        return DB::select("
         with data (date_unit, total, type, balance_type, detail_type) AS (
            SELECT LAST_DAY(tl.date) as date_unit, tl.amount * tl.type, tl.type, accounts.balance_type, adt.name
            FROM transaction_lines tl
            INNER JOIN transactions t on tl.transaction_id = t.id
            INNER JOIN accounts on tl.account_id = accounts.id
            INNER JOIN account_detail_types adt on adt.id = accounts.account_detail_type_id
            WHERE t.STATUS = 'verified' AND tl.date <= :monthDate
            AND adt.name IN ('cash', 'cash_on_hand', 'bank', 'savings', 'credit_card')
            AND tl.team_id = :teamId
            AND balance_type IS NOT null
         )
          SELECT date_unit,
          SUM(SUM(CASE WHEN balance_type = 'debit' THEN total ELSE 0 END)) over (ORDER BY date_unit) as assets,
          SUM(SUM(CASE WHEN balance_type = 'credit' THEN total ELSE 0 END)) over (ORDER BY date_unit) as debts
          FROM DATA
          GROUP BY date_unit
          ORDER BY date_unit DESC
          LIMIT 12;
        ", [
            'teamId' => $teamId,
            'monthDate' => $endDate
        ]);
    }

    public static function getIncomeVsExpenses($teamId, $timeUnitDiff = 2, $timeUnit = 'month', $type = 'expenses')
    {
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $startDate = Carbon::now()->subMonth($timeUnitDiff)->startOfMonth()->format('Y-m-d');

        $expenses = self::getInPeriod($teamId, $startDate, $endDate);
        $expensesGroup = $expenses->groupBy('date');
        $expensesCategories = $expenses->unique('id');
        $expensesCategoriesGroup = $expenses->sortBy('index_field')->mapToGroups(function ($item) {
            return ["{$item->group_name}:{$item->name}" => $item];
        });

        $income = self::getIncomeByPayeeInPeriod($teamId, $startDate, $endDate);
        $incomeCategories = $income->unique('id')->sortBy('index_field')->values();
        $incomeCategoriesGroup = $income->sortBy('index_field')->mapToGroups(function ($item) {
            return [$item->name => $item];
        });

        $dates = $expensesGroup->keys();
        $datesCount = count($dates);

        return
        [
            'dateUnits' => $dates,
            'incomeCategories' => $incomeCategories,
            'incomes' => $incomeCategoriesGroup->map(function ($monthItems) use ($datesCount) {
                $total = $monthItems->sum('total');

                return array_merge([
                    'id' => $monthItems->first()->id,
                    'name' => $monthItems->first()->name,
                    'avg' => Money::of($total, 'USD', null, RoundingMode::HALF_EVEN)
                        ->dividedBy($datesCount, RoundingMode::HALF_EVEN)->getAmount(),
                    'total' => Money::of($total, 'USD', null, RoundingMode::HALF_EVEN)->getAmount(),
                ],
                    $monthItems->mapWithKeys(function ($item) {
                        return [$item->date => $item->total];
                    })->toArray(),
                );
            }),
            'expenseCategories' => $expensesCategories->groupBy('group_name'),
            'expenses' => $expensesCategoriesGroup->map(function ($monthItems) use ($datesCount) {
                $total = $monthItems->sum('total');

                return array_merge([
                    'id' => $monthItems->first()->id,
                    'group_id' => $monthItems->first()->group_id,
                    'name' => $monthItems->first()->name,
                    'group_name' => $monthItems->first()->group_name,
                    'index_field' => $monthItems->first()->index_field,
                    'avg' => Money::of($total, 'USD', null, RoundingMode::HALF_EVEN)
                        ->dividedBy($datesCount, RoundingMode::HALF_EVEN)->getAmount(),
                    'total' => Money::of($total, 'USD', null, RoundingMode::HALF_EVEN)->getAmount(),
                ],
                    $monthItems->mapWithKeys(function ($item) {
                        return [$item->date => $item->total];
                    })->toArray(),
                );
            }),
        ];
    }

    public static function getInPeriod($teamId, $startDate, $endDate)
    {
        return DB::table('categories')
            ->selectRaw('sum(COALESCE(total,0)) as total, date_format(transactions.date, "%Y-%m-01") as date, categories.name, categories.id, pc.id group_id, pc.name group_name, concat(pc.index, ".", categories.index) index_field')
            ->where([
                'categories.team_id' => $teamId,
                'categories.resource_type' => 'transactions',
                'transactions.direction' => Transaction::DIRECTION_CREDIT,
                'transactions.status' => 'verified',
            ])->whereNotNull('categories.parent_id')
            ->where('pc.display_id', '!=', 'inflow')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->groupByRaw('categories.id, date_format(transactions.date, "%Y-%m-01")')
            ->orderByRaw('date_format(transactions.date, "%Y-%m-01"), concat(pc.index,"." ,categories.index)')
            ->leftJoin('transactions', 'transactions.category_id', '=', 'categories.id')
            ->join(DB::raw('categories pc'), 'pc.id', '=', 'categories.parent_id')
            ->get();
    }

    public static function getForExport($teamId)
    {
        return DB::table('transactions')
            ->selectRaw("
        accounts.name AS 'account',
        '' as flag,
        transactions.date AS 'date',
        (CASE WHEN transactions.is_transfer
           THEN concat('Transfer :', ca.name)
           ELSE payees.name END) AS 'payee',
        concat(groups.name, '/', categories.name) as group_category,
        groups.name group_name,
        categories.name category_name,
        (CASE WHEN split.id is not null THEN split.concept ELSE transactions.description END) as memo ,
        (CASE WHEN transactions.direction <> 'DEPOSIT'
        THEN concat(transactions.currency_code, '$', (CASE WHEN split.id is not null THEN split.amount ELSE transactions.total END))
        ELSE  0 END) as outflow,
        (CASE WHEN transactions.direction = 'DEPOSIT'
           THEN concat(transactions.currency_code, '$', (CASE WHEN split.id is not null THEN split.amount ELSE transactions.total END))
           ELSE 0 END) as inflow,
       'reconciled' as cleared
        ")
            ->where([
                'transactions.team_id' => $teamId,
                'transactions.status' => 'verified',
            ])
            ->orderByRaw('transactions.date, concat(groups.index,"." , categories.index)')
            ->leftJoin('categories', 'transactions.category_id', '=', 'categories.id')
            ->leftJoin(DB::raw('categories groups'), 'groups.id', '=', 'categories.parent_id')
            ->leftJoin('payees', 'transactions.payee_id', '=', 'payees.id')
            ->leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->leftJoin(DB::raw('accounts ca'), 'ca.id', '=', 'transactions.counter_account_id')
            ->leftJoin(DB::raw('transaction_lines split'), fn ($q) => $q->on('split.transaction_id', '=', 'transactions.id')->where('is_split', true))
            ->get()
            ->toArray();
    }

    public static function getIncomeByPayeeInPeriod($teamId, $startDate, $endDate)
    {
        return DB::table('payees')
            ->selectRaw('sum(COALESCE(total,0)) as total, date_format(transactions.date, "%Y-%m-01") as date, payees.name, payees.id')
            ->where([
                'payees.team_id' => $teamId,
                'transactions.direction' => Transaction::DIRECTION_DEBIT,
                'transactions.status' => 'verified',
            ])
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->groupByRaw('payees.id, date_format(transactions.date, "%Y-%m-01")')
            ->orderByRaw('date_format(transactions.date, "%Y-%m-01"), payees.name')
            ->join('transactions', 'transactions.payee_id', '=', 'payees.id')
            ->get();
    }

    public static function getPayeeMovementsInPeriod($teamId, $startDate, $endDate, $direction = Transaction::DIRECTION_DEBIT)
    {
        $query = DB::table('payees')
            ->selectRaw('sum(COALESCE(total,0)) as total, payees.name, payees.id')
            ->where([
                'payees.team_id' => $teamId,
                'transaction_lines.type' => $direction == Transaction::DIRECTION_DEBIT ? 1 : -1,
                'transactions.status' => 'verified',
            ]);

        if ($direction == Transaction::DIRECTION_CREDIT) {
            $query->whereNot('direction', Transaction::DIRECTION_DEBIT);
        }

        return $query->whereBetween('transactions.date', [$startDate, $endDate])
            ->groupByRaw('payees.id')
        // ->orderByRaw('total')
            ->join('transaction_lines', 'transaction_lines.payee_id', '=', 'payees.id')
            ->join('transactions', 'transaction_lines.transaction_id', '=', 'transactions.id');
    }

    // splits

    public static function getSplits($teamId, $options)
    {
        return self::getBareSplits($teamId, $options)->get();
    }

    public static function getBareSplits($teamId, $options)
    {
        return Transaction::query()
            ->where([
                'team_id' => $teamId,
                'status' => Transaction::STATUS_VERIFIED,
            ])
            ->whereHas('lines', function ($query) use ($options) {
                if (isset($options['categoryId'])) {
                    $query->where('category_id', $options['categoryId']);
                }
                if (isset($options['accountId'])) {
                    $query->where('account_id', $options['accountId']);
                }
                if (isset($options['groupId'])) {
                    $query->whereHas('category', function ($query) use ($options) {
                        $query->where('parent_id', $options['groupId']);
                    });
                }
            })
            ->with(['splits', 'payee', 'category', 'splits.payee', 'account', 'counterAccount'])
            ->orderByDesc('date')
            ->whereBetween('date', [$options['startDate'], $options['endDate']])
            ->when(isset($options['limit']), fn ($query) => $query->limit($options['limit']));
    }

    public function getCreditCardSpentTransactions(int $teamId) {
        return  DB::table(DB::raw('categories g'))
        ->selectRaw('SUM(transaction_lines.amount * transaction_lines.type) as total,
          accounts.id account_id,
          date_format(transactions.date, "%Y-%m-01") as date,
          accounts.display_id account_display_id,
          accounts.name account_name,
          accounts.alias account_alias,
          categories.name,
          categories.id,
          categories.display_id,
          categories.alias,
          g.display_id groupName,
          g.alias groupAlias,
          MONTH(transactions.date) as months'
        )
        ->groupByRaw('transactions.id')
        ->join('categories', 'g.id', '=', 'categories.parent_id')
        ->join('accounts', 'accounts.category_id', '=', 'categories.id')
        ->join('transaction_lines', 'transaction_lines.account_id', '=', 'accounts.id')
        ->join('transactions', fn ($q)=> $q->on('transactions.id',  'transaction_lines.transaction_id'))
        ->orderByRaw('g.index,categories.index, accounts.index,accounts.number')
        ->where(fn ($q) => $q->where([
            'transactions.status' => Transaction::STATUS_VERIFIED,
          ])->orWhereNull('transactions.status')
        )
        ->where([
            'accounts.team_id' => $teamId,
            'g.display_id' => 'liabilities',
          ])
        ->get();
      }
}
