<?php

namespace App\Domains\Transaction\Traits;

use App\Domains\AppCore\Models\Category;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Transaction;

trait TransactionLineTrait {
    public function scopeDraft($query) {
        return $query->where('status', 'draft');
    }

    public function scopeVerified($query) {
        return $query->where('transactions.status', 'verified')
        ->join('transactions', 'transaction_lines.transaction_id', '=', 'transactions.id');;
    }

    public function scopeByTeam($query, $teamId) {
        return $query->where('transaction_lines.team_id', $teamId);
    }

    public function scopePlanned($query, $isAutomatic = false) {
        return  $query->where('transactions.status', 'planned')
        ->whereHas('schedule', function($query) use ($isAutomatic) {
            $query->where('automatic', $isAutomatic);
        });
    }

    public function scopeInDateFrame($query, $startDate = null, $endDate = null, $orderByDate = true) {
        return $query
        ->when($startDate && !$endDate, function ($query) use ($startDate) {
            $query->where("transaction_lines.date", '=',  $startDate);
        })
        ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            $query->where("transaction_lines.date", '>=',  $startDate);
            $query->where("transaction_lines.date", '<=', $endDate);
        })
        ->when($orderByDate, function ($query) {
            $query->orderBy('transaction_lines.date', 'desc');
        });
    }

    public function scopeExpenses($query) {
        return $query->where([
            'transactions.direction' => Transaction::DIRECTION_CREDIT,
            'transactions.status' => 'verified'
        ])
        ->whereNotNull('transaction_lines.category_id');
    }

    public function scopeBalance($query) {
        $transactionsTotalSum = "ABS(sum(transaction_lines.amount * transaction_lines.type)) as total_amount";
        return $query->where([
            'transactions.status' => 'verified'
        ])
        ->whereNotNull('transaction_lines.category_id')
        ->selectRaw($transactionsTotalSum);
    }

    public function scopeForAccount($query, $accountId) {
        return $query->where(DB::raw("(account_id = $accountId)"));
    }

    public function scopeCategories($query, array $categories) {
        return $query->whereIn("transaction_lines.category_id", $categories)
        ->join('categories', 'transaction_lines.category_id', '=', 'categories.id')
        ->when($categories, fn ($query) => $query->whereIn("transaction_lines.category_id", $categories));
    }

    public function scopeExpenseCategories($query, array $categories = null) {
        $query->whereNot('categories.name', Category::READY_TO_ASSIGN)
        ->join('categories', 'transaction_lines.category_id', '=', 'categories.id');

        if ($categories) {
            $query->whereIn("transaction_lines.category_id", $categories);
        }
        return $query;
    }

    public function scopePayees($query, array $payees) {
        return $query->whereIn("payee_id", $payees);
    }
}


