<?php

namespace App\Domains\Transaction\Traits;

use App\Domains\AppCore\Models\Category;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Transaction;

trait TransactionTrait {
    public function scopeDraft($query) {
        return $query->where('status', 'draft');
    }

    public function scopeVerified($query) {
        return $query->where('transactions.status', 'verified');
    }

    public function scopeByTeam($query, $teamId) {
        return $query->where('transactions.team_id', $teamId);
    }

    public function scopePlanned($query, $isAutomatic = false) {
        return  $query->where('status', 'planned')
        ->whereHas('schedule', function($query) use ($isAutomatic) {
            $query->where('automatic', $isAutomatic);
        });
    }

    public function scopeInDateFrame($query, $startDate = null, $endDate = null, $orderByDate = true) {
        return $query
        ->when($startDate && !$endDate, function ($query) use ($startDate) {
            $query->where("date", '=',  $startDate);
        })
        ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            $query->where("date", '>=',  $startDate);
            $query->where("date", '<=', $endDate);
        })
        ->when($orderByDate, function ($query) {
            $query->orderBy('date', 'desc');
        });
    }

    public function scopeExpenses($query) {
        return $query->where([
            'transactions.direction' => Transaction::DIRECTION_CREDIT,
            'transactions.status' => 'verified'
        ])
        ->whereNotNull('category_id');
    }

    public function scopeBalance($query) {
        $transactionsTotalSum = "ABS(sum(CASE
        WHEN transactions.direction = 'WITHDRAW'
        THEN transactions.total * -1
        ELSE transactions.total * 1 END)) as total";
        return $query->where([
            'transactions.status' => 'verified'
        ])
        ->whereNotNull('category_id')
        ->selectRaw($transactionsTotalSum);
    }

    public function scopeForAccount($query, $accountId) {
        return $query->where(DB::raw("(account_id = $accountId)"));
    }

    public function scopeCategories($query, array $categories) {
        return $query->whereIn("category_id", $categories);
    }

    public function scopeExpenseCategories($query) {
        return  $query->whereNot('categories.name', Category::READY_TO_ASSIGN)
        ->join('categories', 'transactions.category_id', '=', 'categories.id');
    }

    public function scopePayees($query, array $payees) {
        return $query->whereIn("payee_id", $payees);
    }
}


