<?php

namespace App\Domains\Transaction\Services;

use Exception;
use App\Domains\AppCore\Models\Planner;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Integration\Concerns\PlannedTransactionDTO;

class PlannedTransactionService
{
    public function add(PlannedTransactionDTO $plannedData)
    {
        $plannedData->status = Transaction::STATUS_PLANNED;

        $transaction = Transaction::where([
            'category_id' => $plannedData->category_id,
            'status' => Transaction::STATUS_PLANNED,
        ])->first();

        try {
            if (!$transaction) {
                $transaction = Transaction::create($plannedData->toArray());
                $transaction->createLines($plannedData->items ?? []);
            } else {
                $transaction->updateTransaction($plannedData->toArray());
            }
            Planner::create(array_merge($plannedData->toArray(), [
                'dateable_type' => Transaction::class,
                'dateable_id' => $transaction->id,
            ]));
        } catch ( Exception $e) {
            dd($plannedData);
        }

    }

    public function getPlanned($teamId)
    {
        return Transaction::where([
            'team_id' => $teamId,
        ])
            ->planned()
            ->orderBy('date')
            ->get()
            ->map(function ($transaction) {
                return Transaction::parser($transaction);
            });
    }


}
