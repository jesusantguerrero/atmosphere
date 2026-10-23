<?php

namespace App\Services;

use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Plan\Entities\FieldValue;
use Modules\Plan\Entities\Plan;

/**
 * Ties a finished shopping trip to the card charge that paid for it, with no one doing it by hand. Money out that
 * has the trip's amount, shows up within a few days of it and (when the trip says where it was) is at the same
 * store is that trip. It runs both ways: when a trip is finished (the charge may already be in) and when a
 * transaction is created or changed (the charge usually arrives later, from the bank).
 *
 * It only links when the match is unique. Two trips for the same amount, or two charges that fit one trip, are
 * left alone rather than guessed.
 */
class ShoppingVisitLinker
{
    /** How far before / after the day the trip was saved a charge may be dated (the bank posts a few days later). */
    private const DAYS_BEFORE = 1;

    private const DAYS_AFTER = 7;

    /** How many of the latest trips are looked at: older ones are not waiting for a charge any more. */
    private const OPEN_VISITS = 60;

    public function __construct(private ShoppingListReviewService $reviews) {}

    /** A trip was just saved: link it to the charge that already paid for it, if there is exactly one. */
    public function linkVisit(Plan $plan, array $visit): ?array
    {
        if (! empty($visit['transaction_id']) || empty($visit['id'])) {
            return null;
        }

        $day = Carbon::parse($visit['completed_at'])->startOfDay();
        $taken = $this->linkedTransactionIds($plan->team_id);
        $candidates = Transaction::query()
            ->where('team_id', $plan->team_id)
            ->where('direction', Transaction::DIRECTION_CREDIT)
            ->whereIn('status', [Transaction::STATUS_VERIFIED, Transaction::STATUS_DRAFT])
            ->where('currency_code', $visit['currency'])
            ->whereBetween('total', [(float) $visit['total'] - 0.01, (float) $visit['total'] + 0.01])
            ->whereDate('date', '>=', $day->copy()->subDays(self::DAYS_BEFORE)->toDateString())
            ->whereDate('date', '<=', $day->copy()->addDays(self::DAYS_AFTER)->toDateString())
            ->when(! empty($visit['payee_id']), fn ($query) => $query->where('payee_id', $visit['payee_id']))
            ->get()
            ->reject(fn ($transaction) => $taken->contains($transaction->id));

        return $candidates->count() === 1 ? $this->reviews->linkVisit($plan, $visit['id'], $candidates->first()->id) : null;
    }

    /** A transaction was created or changed: link it to the trip it paid for, if there is exactly one. */
    public function linkTransaction($transaction): ?array
    {
        if ($transaction->direction !== Transaction::DIRECTION_CREDIT || ! in_array($transaction->status, [Transaction::STATUS_VERIFIED, Transaction::STATUS_DRAFT], true)) {
            return null;
        }
        if ($this->linkedTransactionIds($transaction->team_id)->contains($transaction->id)) {
            return null;
        }

        $matches = $this->openVisits($transaction->team_id)->filter(fn (array $row) => $this->matches($row['visit'], $transaction))->values();
        if ($matches->count() !== 1) {
            return null;
        }

        $plan = Plan::query()->where('team_id', $transaction->team_id)->find($matches->first()['plan_id']);

        return $plan ? $this->reviews->linkVisit($plan, $matches->first()['visit']['id'], $transaction->id) : null;
    }

    private function matches(array $visit, $transaction): bool
    {
        if ($transaction->currency_code !== $visit['currency'] || abs((float) $transaction->total - (float) $visit['total']) >= 0.01) {
            return false;
        }
        if (! empty($visit['payee_id']) && (int) $transaction->payee_id !== (int) $visit['payee_id']) {
            return false;
        }

        $day = Carbon::parse($visit['completed_at'])->startOfDay();
        $date = Carbon::parse($transaction->date)->startOfDay();

        return $date->betweenIncluded($day->copy()->subDays(self::DAYS_BEFORE), $day->copy()->addDays(self::DAYS_AFTER));
    }

    /**
     * The team's latest trips that are not tied to a charge (or whose charge was deleted since).
     *
     * @return Collection<int, array{plan_id: int, visit: array<string, mixed>}>
     */
    private function openVisits(int $teamId): Collection
    {
        $rows = $this->visitRows($teamId);
        $alive = Transaction::query()->where('team_id', $teamId)->whereIn('id', $rows->pluck('visit.transaction_id')->filter()->all())->pluck('id');

        return $rows->filter(fn (array $row) => empty($row['visit']['transaction_id']) || ! $alive->contains($row['visit']['transaction_id']))->values();
    }

    /** @return Collection<int, int> The transactions that already paid for a trip. */
    private function linkedTransactionIds(int $teamId): Collection
    {
        return $this->visitRows($teamId)->pluck('visit.transaction_id')->filter()->map(fn ($id) => (int) $id)->values();
    }

    /** @return Collection<int, array{plan_id: int, visit: array<string, mixed>}> */
    private function visitRows(int $teamId): Collection
    {
        return FieldValue::query()
            ->where(['team_id' => $teamId, 'entity_type' => Plan::class, 'resource' => 'plan'])
            ->where('field_name', 'like', 'shopping_visit_%')
            ->latest('id')
            ->limit(self::OPEN_VISITS)
            ->get(['entity_id', 'value'])
            ->map(fn ($row) => ['plan_id' => (int) $row->entity_id, 'visit' => json_decode($row->value, true) ?: []])
            ->filter(fn (array $row) => ! empty($row['visit']['id']) && ! empty($row['visit']['completed_at']))
            ->values();
    }
}
