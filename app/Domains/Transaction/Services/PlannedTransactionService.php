<?php

namespace App\Domains\Transaction\Services;

use Exception;
use App\Domains\AppCore\Models\Planner;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Integration\Concerns\PlannedTransactionDTO;
use App\Domains\Transaction\Services\NextPaymentsService;
use Insane\Journal\Models\Core\Transaction as CoreTransaction;

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
        // Use the new NextPaymentsService for unified next payments
        $nextPaymentsService = app(NextPaymentsService::class);
        return $nextPaymentsService->getNextPayments($teamId);
    }

    public function getForNotificationType()
    {
        return Transaction::where([
            "status" => Transaction::STATUS_PLANNED
        ])
        ->whereRaw("DATEDIFF(date, now()) <= 3")
        ->get();

    }

    public function find(Transaction|CoreTransaction $transaction, string $month): ?Transaction
    {
        return Transaction::where([
            'category_id' => $transaction->category_id,
            'status' => Transaction::STATUS_PLANNED,
            'team_id' => $transaction->team_id,
        ])
        ->whereHas('schedule', function ($query) {
            $query->whereNull('completed_at');
        })
        ->whereRaw("total <= ?", [$transaction->total])
        ->whereRaw("format(date, 'YYYY-MM') >= ?", '2025-06')
        ->first();
    }

    public function update(PlannedTransactionDTO $plannedData)
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

}
