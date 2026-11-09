<?php

namespace App\Domains\Transaction\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Integration\Concerns\PlannedTransactionDTO;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\Transaction as CoreTransaction;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\ArrayTransformerConfig;

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

    /**
     * The planned payment a verified transaction pays, with the same rules as the
     * "needs your attention" list (PaymentMatcher): category, payee or destination
     * account, ±10% or partial payments, in the payment's month (plus a week ahead).
     */
    public function find(Transaction|CoreTransaction $transaction, string $month): ?Transaction
    {
        $matcher = app(PaymentMatcher::class);
        $start = Carbon::parse($month)->startOfMonth();

        $planned = Transaction::query()
            ->where('team_id', $transaction->team_id)
            ->where('status', Transaction::STATUS_PLANNED)
            ->where('direction', $transaction->direction)
            ->whereBetween('date', [$start->format('Y-m-d'), $start->copy()->endOfMonth()->addDays(PaymentMatcher::EARLY_DAYS)->format('Y-m-d')])
            ->whereHas('schedule', fn ($query) => $query->whereNull('completed_at'))
            ->with('schedule')
            ->get()
            ->keyBy(fn (Transaction $plan) => "planned_{$plan->id}");

        $items = $planned->map(fn (Transaction $plan, string $id) => [
            'id' => $id,
            'total' => abs((float) $plan->total),
            'due_date' => substr((string) $plan->date, 0, 10),
            'direction' => $plan->direction,
            'keys' => [
                'category_id' => $plan->category_id,
                'payee_id' => $plan->payee_id,
                'counter_account_id' => $plan->counter_account_id,
            ],
            'window' => $matcher->windowFor(substr((string) $plan->date, 0, 10), 'planned_transaction'),
        ])->filter(fn (array $item) => substr((string) $transaction->date, 0, 10) >= $item['window'][0]
            && substr((string) $transaction->date, 0, 10) <= $item['window'][1])
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        $paid = $matcher->allocate($items, $matcher->candidates(
            (int) $transaction->team_id,
            $items->min(fn (array $item) => $item['window'][0]),
            $items->max(fn (array $item) => $item['window'][1]),
        ));

        foreach ($paid as $itemId => $transactionIds) {
            if (in_array((int) $transaction->id, $transactionIds, true)) {
                return $planned->get($itemId);
            }
        }

        return null;
    }

    /**
     * Record that `$payment` paid this cycle of `$planned`. A recurring plan moves
     * to its next date and stays open; a one-off plan is completed.
     */
    public function completeOccurrence(Transaction $planned, Transaction|CoreTransaction $payment): void
    {
        $schedule = $planned->schedule;
        try {
            $nextDate = $schedule?->frequency ? $this->nextDate($schedule) : null;
        } catch (\Throwable $exception) {
            report($exception);

            return;
        }

        if (! $nextDate || ($schedule->end_date && $nextDate > Carbon::parse($schedule->end_date)->format('Y-m-d'))) {
            $schedule?->markAsCompleted($payment, 'Automatically completed by actual transaction');

            return;
        }

        $planned->update(['date' => $nextDate]);
        $schedule->update([
            'date' => $nextDate,
            'completed_resource_id' => $payment->id,
            'completed_resource_type' => $payment->getMorphClass(),
            'completion_notes' => 'Paid by an actual transaction; moved to the next date',
        ]);
    }

    private function nextDate(Planner $schedule): ?string
    {
        // Planner stores DAILY | WEEK_DAY | WEEK | MONTHLY; Recurr speaks RRULE.
        $rule = (new Rule)
            ->setStartDate(new \DateTime(Carbon::parse($schedule->date)->format('Y-m-d')))
            ->setFreq(match ($schedule->frequency) {
                'WEEK' => 'WEEKLY',
                'WEEK_DAY' => 'DAILY',
                default => $schedule->frequency,
            })
            ->setCount(2);
        if ($schedule->frequency === 'WEEK_DAY') {
            $rule->setByDay(['MO', 'TU', 'WE', 'TH', 'FR']);
        }

        $config = new ArrayTransformerConfig;
        $config->enableLastDayOfMonthFix();
        $transformer = new ArrayTransformer;
        $transformer->setConfig($config);

        return $transformer->transform($rule)[1]?->getStart()->format('Y-m-d');
    }

    public function update(PlannedTransactionDTO $plannedData)
    {
        $plannedData->status = Transaction::STATUS_PLANNED;

        $transaction = Transaction::where([
            'category_id' => $plannedData->category_id,
            'status' => Transaction::STATUS_PLANNED,
        ])->first();

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
}
