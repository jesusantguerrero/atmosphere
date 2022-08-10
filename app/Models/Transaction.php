<?php

namespace App\Models;

use App\Models\Planner;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Transaction as CoreTransaction;

class Transaction extends CoreTransaction
{
    const STATUS_PLANNED = 'planned';
    protected $with = ['schedule'];


    public function schedule() {
        return $this->morphOne(Planner::class, 'dateable', 'dateable_type', 'dateable_id', );
    }

    public function categoryGroup() {
        return $this->hasOneThrough(Category::class, Category::class, 'parent_id', 'parent_id');
    }

    public function copy($data = []) {
        $transactionNew = Self::create(array_merge($this->toArray(), $data));
        foreach ($this->lines as $line) {
            $transactionNew->lines()->create($line->toArray());
        }
        return $transactionNew;
    }


    public static function parser($transaction, $isGrouped = false) {
        if (!$isGrouped) {
            return [
                'id' => $transaction->id,
                'date' => $transaction->date,
                'number' => $transaction->number,
                'direction' => $transaction->direction,
                'payee' => $transaction->payee,
                'description' => $transaction->description,
                'direction' => $transaction->direction,
                'account' => $transaction->mainLine ? $transaction->mainLine->account: [],
                'category' => $transaction->category ? $transaction->category->account : [],
                'transactionCategory' => $transaction->transactionCategory,
                'currency_code' => $transaction->currency_code,
                'total' => $transaction->total,
                'lines' => $transaction->lines,
                'mainLine' => $transaction->mainLine,
                'schedule' => $transaction->schedule,
            ];
        } else {
            return [
                'description' => $transaction->description,
                'account' => $transaction->mainLine ? $transaction->mainLine->account: [],
                'category' => $transaction->category ? $transaction->category->account : [],
                'currency_code' => $transaction->currency_code,
                'total' => $transaction->total,
            ];
        }
    }

    public static function getExpenses($teamId, $startDate, $endDate) {
        return self::where([
            'team_id' => $teamId,
            'direction' => CoreTransaction::DIRECTION_CREDIT,
            'status' => 'verified'
        ])
        ->whereNotNull('transaction_category_id')
        ->getByMonth($startDate, $endDate)
        ->groupBy(['transactions.id', 'currency_code'])
        ->get();
    }

    public static function getExpensesTotal($teamId, $startDate, $endDate) {
        return self::where([
            'team_id' => $teamId,
            'direction' => CoreTransaction::DIRECTION_CREDIT,
            'status' => 'verified'
        ])
        ->whereNotNull('transaction_category_id')
        ->whereBetween('date', [$startDate, $endDate])
        ->select(DB::raw('SUM(total) as total, currency_code'))
        ->groupBy('currency_code')
        ->get();
    }

    public static function getCategoryExpenses($teamId, $startDate, $endDate, $limit = null, $parentId = null) {
        $builder = self::where([
            'transactions.team_id' => $teamId,
            'direction' => CoreTransaction::DIRECTION_CREDIT,
            'transactions.status' => 'verified'
        ])
        ->whereNotNull('transaction_category_id')
        ->getByMonth($startDate, $endDate, false);

        if ($parentId) {
            $builder->where("categories.parent_id", $parentId);
        }

        return $builder->groupBy('transaction_category_id')
        ->select(DB::raw('sum(total) as total, transaction_category_id, categories.name'))
        ->join('categories', 'categories.id', 'transaction_category_id')
        ->orderBy('total', 'desc')
        ->limit($limit)
        ->get();
    }

    public static function getCategoryExpensesGroup($teamId, $startDate, $endDate, $limit = null) {
        $builder = self::where([
            'transactions.team_id' => $teamId,
            'direction' => CoreTransaction::DIRECTION_CREDIT,
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
        return self::where([
            'team_id' => $teamId,
            'status' => 'verified'
        ])
        ->byCategories(['inflow'], $teamId)
        ->getByMonth($startDate, $endDate)
        ->sum('total');
    }

    public static function getDrafts($teamId) {
        return self::where([
            'team_id' => $teamId,
            'status' => 'draft'
        ])->orderBy('date', 'desc')->get();
    }
}
