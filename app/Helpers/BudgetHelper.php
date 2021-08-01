<?php

namespace App\Helpers;

use App\Models\Transaction;

class BudgetHelper
{
    public static function getPlannedTransactions($teamId, $isAutomatic = 0) {
        return Transaction::where([
            'team_id' => $teamId,
            'status' => 'planned'
        ])
        ->whereHas('schedule', function($query) use ($isAutomatic) {
            $query->where('automatic', $isAutomatic);
        })
        ->get()
        ->map(function ($transaction) {
            return Transaction::parser($transaction);
        });

    }
}
