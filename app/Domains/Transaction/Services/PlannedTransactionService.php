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
    /**
     * Save a planned (recurring) transaction and its schedule. By default a plan
     * of the same household category is reused (budget targets keep one plan per
     * category); pass `$reuseCategoryPlan = false` to always start a new one.
     */
    public function add(PlannedTransactionDTO $plannedData, bool $reuseCategoryPlan = true): Transaction
    {
        $plannedData->status = Transaction::STATUS_PLANNED;

        $transaction = $reuseCategoryPlan && $plannedData->category_id
            ? Transaction::where([
                'team_id' => $plannedData->team_id,
                'category_id' => $plannedData->category_id,
                'status' => Transaction::STATUS_PLANNED,
            ])->first()
            : null;

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
                'frequency_interval' => max(1, $plannedData->frequency_interval),
                'dateable_type' => Transaction::class,
                'dateable_id' => $transaction->id,
            ]
        ));

        return $transaction;
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
        $paidOn = substr((string) $transaction->date, 0, 10);

        // Overdue plans from previous months can still be paid late.
        $planned = Transaction::query()
            ->where('team_id', $transaction->team_id)
            ->where('status', Transaction::STATUS_PLANNED)
            ->where('direction', $transaction->direction)
            ->whereBetween('date', [$start->copy()->subMonthsNoOverflow(PaymentMatcher::OVERDUE_MONTHS)->format('Y-m-d'), $start->copy()->endOfMonth()->addDays(PaymentMatcher::EARLY_DAYS)->format('Y-m-d')])
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
            'window' => $matcher->windowFor(substr((string) $plan->date, 0, 10), 'planned_transaction', $paidOn),
        ])->filter(fn (array $item) => $paidOn >= $item['window'][0] && $paidOn <= $item['window'][1])
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

    /**
     * The occurrence after the schedule's current date, honoring its interval.
     */
    public function nextDate(Planner $schedule): ?string
    {
        return $this->upcomingDates(
            Carbon::parse($schedule->date)->format('Y-m-d'),
            (string) $schedule->frequency,
            (int) ($schedule->frequency_interval ?? 1),
            2,
        )[1] ?? null;
    }

    /**
     * `$count` occurrences starting at `$from` (included): every `$interval`
     * days, weekdays, weeks or months.
     *
     * @return list<string>
     */
    public function upcomingDates(string $from, string $frequency, int $interval = 1, int $count = 3): array
    {
        // Planner stores DAILY | WEEK_DAY | WEEK | MONTHLY (the web form sends WEEKLY); Recurr speaks RRULE.
        $frequency = strtoupper($frequency);
        $rule = (new Rule)
            ->setStartDate(new \DateTime($from))
            ->setFreq(match ($frequency) {
                'WEEK' => 'WEEKLY',
                'WEEK_DAY' => 'DAILY',
                default => $frequency,
            })
            ->setInterval($frequency === 'WEEK_DAY' ? 1 : max(1, $interval))
            ->setCount($count);
        if ($frequency === 'WEEK_DAY') {
            $rule->setByDay(['MO', 'TU', 'WE', 'TH', 'FR']);
        }

        $config = new ArrayTransformerConfig;
        $config->enableLastDayOfMonthFix();
        $transformer = new ArrayTransformer;
        $transformer->setConfig($config);

        return array_values(array_map(
            fn ($occurrence) => $occurrence->getStart()->format('Y-m-d'),
            $transformer->transform($rule)->toArray(),
        ));
    }

    public function update(PlannedTransactionDTO $plannedData): Transaction
    {
        return $this->add($plannedData);
    }
}
