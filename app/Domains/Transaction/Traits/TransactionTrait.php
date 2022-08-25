<?php

namespace App\Domains\Transaction\Traits;

use Insane\Journal\Models\Core\Transaction;

trait TransactionTrait {
    public function scopeDraft($query) {
        return $query->where('status', 'draft');
    }

    public function scopeVerified($query) {
        return $query->where('status', 'verified');
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
            'direction' => Transaction::DIRECTION_CREDIT,
            'status' => 'verified'
        ])
        ->whereNotNull('transaction_category_id');
    }

    public function scopeForAccount($query, $accountId) {
        return $query->where('account_id', $accountId);
    }
}


