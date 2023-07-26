<?php

namespace App\Domains\Transaction\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Integration\Concerns\PlannedTransactionDTO;
use App\Domains\Transaction\Models\Transaction;

class PlannedTransactionService {
    public function add(PlannedTransactionDTO $postData) {
        $postData->status = Transaction::STATUS_PLANNED;

        $transaction = Transaction::where([
            "category_id" => $postData->category_id,
            "status" => Transaction::STATUS_PLANNED
        ])->first();

        if (!$transaction) {
            $transaction = Transaction::create($postData->toArray());
            $transaction->createLines($postData->items ?? []);
        } else {
            $transaction->updateTransaction($postData->toArray());
        }

        Planner::create(array_merge($postData->toArray() ,[
            'dateable_type' => Transaction::class,
            'dateable_id' => $transaction->id,
        ]));
    }

    public function getPlanned($teamId) {
        return Transaction::where([
            'team_id' => $teamId,
        ])
        ->planned()
        ->whereRaw("date >= ?", [ now()->format('Y-m-d')])
        ->orderBy('date')
        ->get()
        ->map(function ($transaction) {
            return Transaction::parser($transaction);
        });
    }
}
