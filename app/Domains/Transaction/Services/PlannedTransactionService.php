<?php

namespace App\Domains\Transaction\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Integration\Concerns\PlannedTransactionDTO;
use Illuminate\Support\Arr;
use App\Domains\Transaction\Models\Transaction;
use Exception;
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

        // Restrict the DTO to real columns before mass-assigning: it carries
        // schedule-only fields (frequency, interval, end_type, items, metaData,
        // timezone_id, resource_type_id, repeat_*) that aren't Transaction/Planner
        // columns. Passing them to create() throws under
        // Model::preventSilentlyDiscardingAttributes (on outside production).
        if (! $transaction) {
            $transaction = Transaction::create(Arr::only($plannedData->toArray(), (new Transaction)->getFillable()));
            $transaction->createLines($plannedData->items ?? []);
        } else {
            $transaction->updateTransaction(Arr::only($plannedData->toArray(), (new Transaction)->getFillable()));
        }

        Planner::create(array_merge(
            Arr::only($plannedData->toArray(), (new Planner)->getFillable()),
            [
                'dateable_type' => Transaction::class,
                'dateable_id' => $transaction->id,
            ]
        ));
    }

    public function getPlanned($teamId)
    {
        // Use the new NextPaymentsService for unified next payments
        $nextPaymentsService = app(NextPaymentsService::class);

        return $nextPaymentsService->getNextPayments($teamId);
    }

    public function getForNotificationType()
    {
        // Notify for planned transactions that:
        //   - are still PLANNED (status check)
        //   - haven't been completed (schedule.completed_at IS NULL)
        //   - are due within 3 days OR up to 14 days past due
        // The completed_at filter is what stops notifications from firing
        // forever after a bill has been paid; the lower bound stops
        // ancient unmatched bills from spamming the user daily.
        return Transaction::where([
            'status' => Transaction::STATUS_PLANNED,
        ])
            ->whereHas('schedule', function ($query) {
                $query->whereNull('completed_at');
            })
            ->whereRaw('DATEDIFF(date, now()) <= 3')
            ->whereRaw('DATEDIFF(date, now()) >= -14')
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
            ->whereRaw('total <= ?', [$transaction->total])
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') >= ?", [now()->format('Y-m')])
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
            if (! $transaction) {
                $transaction = Transaction::create($plannedData->toArray());
                $transaction->createLines($plannedData->items ?? []);
            } else {
                $transaction->updateTransaction($plannedData->toArray());
            }
            Planner::create(array_merge($plannedData->toArray(), [
                'dateable_type' => Transaction::class,
                'dateable_id' => $transaction->id,
            ]));
        } catch (Exception $e) {
            dd($plannedData);
        }
    }
}
