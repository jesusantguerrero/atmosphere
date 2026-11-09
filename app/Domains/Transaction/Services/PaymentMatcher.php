<?php

namespace App\Domains\Transaction\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Decides which upcoming payments are already paid by a verified transaction,
 * whatever way it entered (typed, bank import, approved draft). Matching keys:
 * same category, same payee or same destination account; the amount must be
 * within ±10%, or several smaller payments of the same key must add up to it.
 * A transaction pays at most one item, and the earliest item claims first.
 */
final class PaymentMatcher
{
    public const TOLERANCE = 0.10;

    /**
     * Planned payments also accept a payment made up to this many days early
     * (paying on Nov 28 the bill due Dec 1).
     */
    public const EARLY_DAYS = 7;

    /**
     * @param  Collection<int, array{id: string, total: float, due_date: string, direction: string, keys: array<string, int|null>, window: array{0: string, 1: string}}>  $items
     * @param  Collection<int, object>  $candidates  verified transactions
     * @return array<string, list<int>> item id => transaction ids that pay it
     */
    public function allocate(Collection $items, Collection $candidates): array
    {
        $claimed = [];
        $matches = [];

        foreach ($items->sortBy('due_date') as $item) {
            $target = (float) $item['total'];
            if ($target <= 0) {
                continue;
            }

            foreach ($item['keys'] as $field => $value) {
                if ($value === null) {
                    continue;
                }

                $pool = $candidates
                    ->reject(fn (object $tx) => isset($claimed[$tx->id]))
                    ->filter(fn (object $tx) => $tx->direction === $item['direction']
                        && (int) $tx->{$field} === (int) $value
                        && $tx->date >= $item['window'][0]
                        && $tx->date <= $item['window'][1])
                    ->sortBy('date')
                    ->values();

                $paidBy = $this->pick($pool, $target);
                if ($paidBy !== []) {
                    foreach ($paidBy as $id) {
                        $claimed[$id] = true;
                    }
                    $matches[$item['id']] = $paidBy;

                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * One transaction within the tolerance, or two or more smaller ones whose sum reaches it.
     *
     * @param  Collection<int, object>  $pool  sorted by date
     * @return list<int>
     */
    private function pick(Collection $pool, float $target): array
    {
        $single = $pool->first(fn (object $tx) => abs((float) $tx->total - $target) <= self::TOLERANCE * $target);
        if ($single) {
            return [(int) $single->id];
        }

        $sum = 0.0;
        $ids = [];
        foreach ($pool as $tx) {
            if ((float) $tx->total > $target * (1 + self::TOLERANCE)) {
                continue;
            }
            $sum += (float) $tx->total;
            $ids[] = (int) $tx->id;
            if (count($ids) >= 2 && $sum >= $target * (1 - self::TOLERANCE)) {
                return $ids;
            }
        }

        return [];
    }

    /**
     * Budget reminders belong to their month; planned payments also accept early payments.
     *
     * @return array{0: string, 1: string}
     */
    public function windowFor(string $dueDate, string $type): array
    {
        $due = Carbon::parse($dueDate);
        $start = $due->copy()->startOfMonth();
        if ($type === 'planned_transaction') {
            $start = $start->min($due->copy()->subDays(self::EARLY_DAYS));
        }

        return [$start->format('Y-m-d'), $due->copy()->endOfMonth()->format('Y-m-d')];
    }

    /**
     * Verified transactions that can still pay something: those already recorded as
     * the completion of a plan (a past cycle) are left out.
     *
     * @return Collection<int, object>
     */
    public function candidates(int $teamId, string $from, string $to): Collection
    {
        $usedByPlans = Planner::query()
            ->where('team_id', $teamId)
            ->where('completed_resource_type', (new Transaction)->getMorphClass())
            ->whereNotNull('completed_resource_id')
            ->pluck('completed_resource_id');

        return Transaction::query()
            ->where('team_id', $teamId)
            ->where('status', Transaction::STATUS_VERIFIED)
            ->whereBetween('date', [$from, $to])
            ->whereNotIn('id', $usedByPlans)
            ->toBase()
            ->get(['id', 'date', 'total', 'direction', 'category_id', 'payee_id', 'counter_account_id'])
            ->map(function (object $tx) {
                $tx->date = substr((string) $tx->date, 0, 10);

                return $tx;
            });
    }
}
