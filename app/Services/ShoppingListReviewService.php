<?php

namespace App\Services;

use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use App\Notifications\ShoppingListReady;
use Illuminate\Support\Str;
use Insane\Journal\Models\Core\Payee;
use Modules\Plan\Entities\FieldValue;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;

class ShoppingListReviewService
{
    private const FIELD = 'shopping_review';

    private const REVISION_KEY = '_revision';

    private function version(Plan $plan): string
    {
        $items = PlanItem::query()->without(['fields', 'checklist'])->where('team_id', $plan->team_id)->where('plan_id', $plan->id)->orderBy('id')->get(['id', 'stage_id', 'title', 'state']);

        return hash('sha256', json_encode([$items->map(fn ($item) => [$item->id, $item->stage_id, $item->title, $item->state === 'skip' ? 'available' : 'needed'])->all(), $this->itemDetails($plan)], JSON_THROW_ON_ERROR));
    }

    public function status(Plan $plan): array
    {
        $value = FieldValue::query()->where(['team_id' => $plan->team_id, 'entity_type' => Plan::class, 'entity_id' => $plan->id, 'resource' => 'plan', 'field_name' => self::FIELD])->value('value');
        $review = $value ? json_decode($value, true) : [];
        $marks = $this->marks($plan);
        $currentVersion = $this->version($plan);

        return [
            'id' => $review['id'] ?? null,
            'status' => ! empty($review['completed_at']) ? 'completed' : (! empty($review['reviewed_at']) ? (($review['version'] ?? null) === $currentVersion ? 'ready' : 'changed') : 'none'),
            'note' => $review['note'] ?? '', 'reviewed_at' => $review['reviewed_at'] ?? null,
            'completed_at' => $review['completed_at'] ?? null, 'version' => $review['version'] ?? null,
            'current_version' => $currentVersion,
            'reviewed_ids' => $this->reviewedIds($plan, $marks),
            'reviewed_revision' => (int) ($marks[self::REVISION_KEY] ?? 0),
            'item_details' => $this->itemDetails($plan),
        ];
    }

    private function store(Plan $plan, array $review, string $fieldName = self::FIELD): void
    {
        $field = $plan->findOrCreateField(['name' => $fieldName, 'title' => 'Revisión de compra', 'type' => 'text']);
        $identity = ['field_id' => $field->id, 'entity_id' => $plan->id, 'entity_type' => Plan::class];
        $value = FieldValue::query()->where($identity)->first() ?? (new FieldValue)->forceFill($identity);
        $value->forceFill(['team_id' => $plan->team_id, 'user_id' => $plan->user_id, 'resource' => 'plan', 'field_name' => $fieldName, 'field_title' => 'Revisión de compra', 'value' => json_encode($review, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)])->save();
    }

    private function itemSignature(PlanItem $item): string
    {
        return hash('sha256', json_encode([$item->title, $item->state === 'skip' ? 'available' : 'needed'], JSON_THROW_ON_ERROR));
    }

    private function marks(Plan $plan): array
    {
        $value = FieldValue::query()->where(['team_id' => $plan->team_id, 'entity_type' => Plan::class, 'entity_id' => $plan->id, 'resource' => 'plan', 'field_name' => 'shopping_reviewed_items'])->value('value');

        return $value ? (json_decode($value, true) ?? []) : [];
    }

    public function reviewedIds(Plan $plan, ?array $marks = null): array
    {
        $marks ??= $this->marks($plan);

        return PlanItem::query()->without(['fields', 'checklist'])->where('team_id', $plan->team_id)->where('plan_id', $plan->id)->get()->filter(fn (PlanItem $item) => ($marks[$item->id] ?? null) === $this->itemSignature($item))->pluck('id')->values()->all();
    }

    public function itemDetails(Plan $plan): array
    {
        $value = FieldValue::query()->where(['team_id' => $plan->team_id, 'entity_type' => Plan::class, 'entity_id' => $plan->id, 'resource' => 'plan', 'field_name' => 'shopping_item_details'])->value('value');

        return $value ? (json_decode($value, true) ?? []) : [];
    }

    public function saveItemDetails(Plan $plan, PlanItem $item, string $quantity, string $note): void
    {
        $plan->getConnection()->transaction(function () use ($plan, $item, $quantity, $note): void {
            Plan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $details = $this->itemDetails($plan);
            if (trim($quantity) === '' && trim($note) === '') {
                unset($details[$item->id]);
            } else {
                $details[$item->id] = ['quantity' => trim($quantity), 'note' => trim($note)];
            }
            $this->store($plan, $details, 'shopping_item_details');
        });
    }

    public function unmarkReviewed(Plan $plan, PlanItem $item): void
    {
        $plan->getConnection()->transaction(function () use ($plan, $item): void {
            Plan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $marks = $this->marks($plan);
            unset($marks[$item->id]);
            $this->storeMarks($plan, $marks);
        });
    }

    public function markReviewed(Plan $plan, PlanItem $item): void
    {
        $plan->getConnection()->transaction(function () use ($plan, $item): void {
            Plan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $marks = $this->marks($plan);
            $marks[$item->id] = $this->itemSignature($item);
            $this->storeMarks($plan, $marks);
        });
    }

    public function clearReviewed(Plan $plan): void
    {
        $plan->getConnection()->transaction(function () use ($plan): void {
            Plan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $this->storeMarks($plan, [self::REVISION_KEY => $this->marks($plan)[self::REVISION_KEY] ?? 0]);
        });
    }

    /**
     * Persist the reviewed marks with a bumped revision. Callers must hold the
     * plan lock. Clients receive every snapshot (toggle responses, Mercure
     * echoes, focus refreshes) out of order, and use the revision to drop
     * snapshots older than the one they already applied.
     *
     * @param  array<int|string, mixed>  $marks
     */
    private function storeMarks(Plan $plan, array $marks): void
    {
        $marks[self::REVISION_KEY] = (int) ($marks[self::REVISION_KEY] ?? 0) + 1;
        $this->store($plan, $marks, 'shopping_reviewed_items');
    }

    private function sessionField(Plan $plan, string $name): array
    {
        $value = FieldValue::query()->where(['team_id' => $plan->team_id, 'entity_type' => Plan::class, 'entity_id' => $plan->id, 'resource' => 'plan', 'field_name' => $name])->value('value');

        return $value ? (json_decode($value, true) ?? []) : [];
    }

    public function activeSession(Plan $plan): ?array
    {
        $active = $this->sessionField($plan, 'shopping_active_session');
        if (! $active) {
            return null;
        }
        $existing = collect($active['items'])->keyBy('id');
        $details = $this->itemDetails($plan);
        $active['items'] = PlanItem::query()->without(['fields', 'checklist'])->with('stage')->where('team_id', $plan->team_id)->where('plan_id', $plan->id)->where(fn ($query) => $query->where('state', PlanItem::STATE_PENDING)->orWhereIn('id', $active['purchased_ids']))->orderBy('order')->get()->map(fn (PlanItem $item) => $existing->get($item->id) ?? ['id' => $item->id, 'title' => $item->title, 'stage_id' => $item->stage_id, 'category' => $item->stage?->name, 'details' => $details[$item->id] ?? null])->all();

        return $active;
    }

    public function visits(Plan $plan): array
    {
        return $this->decorate($plan->team_id, FieldValue::query()->where(['team_id' => $plan->team_id, 'entity_type' => Plan::class, 'entity_id' => $plan->id, 'resource' => 'plan'])->where('field_name', 'like', 'shopping_visit_%')->latest('id')->limit(20)->pluck('value')->map(fn (string $value) => json_decode($value, true))->all());
    }

    /** One finished trip by its session id, or null. */
    public function visit(Plan $plan, string $sessionId): ?array
    {
        return $this->sessionField($plan, 'shopping_visit_'.$sessionId) ?: null;
    }

    /**
     * Links a visit to the transaction that paid for it (the card charge, which usually arrives after the trip), or
     * unlinks it with null. The link is the transaction's id inside the visit; nothing else is touched.
     */
    public function linkVisit(Plan $plan, string $sessionId, ?int $transactionId): array
    {
        return $plan->getConnection()->transaction(function () use ($plan, $sessionId, $transactionId) {
            Plan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $field = 'shopping_visit_'.$sessionId;
            $visit = $this->sessionField($plan, $field);
            abort_if(! $visit, 404);
            if ($transactionId === null) {
                unset($visit['transaction_id']);
            } else {
                $visit['transaction_id'] = $transactionId;
            }
            $this->store($plan, $visit, $field);

            return $this->decorate($plan->team_id, [$visit])[0];
        });
    }

    /** Changes (or clears with null) the store a finished trip was at. The transaction link is left as it is. */
    public function setVisitPayee(Plan $plan, string $sessionId, ?int $payeeId): array
    {
        return $plan->getConnection()->transaction(function () use ($plan, $sessionId, $payeeId) {
            Plan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $field = 'shopping_visit_'.$sessionId;
            $visit = $this->sessionField($plan, $field);
            abort_if(! $visit, 404);
            if ($payeeId === null) {
                unset($visit['payee_id']);
            } else {
                $visit['payee_id'] = $payeeId;
            }
            $this->store($plan, $visit, $field);

            return $visit;
        });
    }

    /**
     * What a visit refers to, resolved when it is read (never stored): the store (`payee`) the trip was at and the
     * transaction (`transaction`) that paid for it, once it has been linked.
     *
     * @param  array<int, array<string, mixed>>  $visits
     * @return array<int, array<string, mixed>>
     */
    public function decorate(int $teamId, array $visits): array
    {
        $payees = Payee::query()->where('team_id', $teamId)->whereIn('id', collect($visits)->pluck('payee_id')->filter()->unique()->values())->pluck('name', 'id');
        $transactions = Transaction::query()->where('team_id', $teamId)->whereIn('id', collect($visits)->pluck('transaction_id')->filter()->unique()->values())->get()->keyBy('id');

        return array_map(function (array $visit) use ($payees, $transactions) {
            $payeeId = $visit['payee_id'] ?? null;
            $transaction = $transactions->get($visit['transaction_id'] ?? null);

            return [
                ...$visit,
                'payee' => $payeeId && $payees->has($payeeId) ? ['id' => (int) $payeeId, 'name' => $payees->get($payeeId)] : null,
                'transaction' => $transaction ? ['id' => $transaction->id, 'description' => $transaction->description, 'total' => (float) $transaction->total, 'currency_code' => $transaction->currency_code, 'date' => (string) $transaction->date] : null,
            ];
        }, $visits);
    }

    public function startSession(Plan $plan, int $userId): array
    {
        return $plan->getConnection()->transaction(function () use ($plan, $userId) {
            Plan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            if ($active = $this->activeSession($plan)) {
                return $active;
            }
            $details = $this->itemDetails($plan);
            $items = PlanItem::query()->without(['fields', 'checklist'])->with('stage')->where('team_id', $plan->team_id)->where('plan_id', $plan->id)->where('state', PlanItem::STATE_PENDING)->orderBy('order')->get()->map(fn (PlanItem $item) => ['id' => $item->id, 'title' => $item->title, 'stage_id' => $item->stage_id, 'category' => $item->stage?->name, 'details' => $details[$item->id] ?? null])->all();
            abort_if(empty($items), 422, 'No hay productos pendientes. Restablece los productos comprados a "pendiente" para iniciar una nueva compra.');
            $session = ['id' => (string) Str::uuid(), 'started_at' => now()->toIso8601String(), 'user_id' => $userId, 'review_id' => $this->status($plan)['id'], 'items' => $items, 'purchased_ids' => []];
            $this->store($plan, $session, 'shopping_active_session');

            return $session;
        });
    }

    public function updateSessionCart(Plan $plan, PlanItem $item): void
    {
        $plan->getConnection()->transaction(function () use ($plan, $item): void {
            Plan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $active = $this->sessionField($plan, 'shopping_active_session') ?: null;
            if (! $active) {
                return;
            }
            if (! in_array($item->id, array_column($active['items'], 'id'), true)) {
                $active['items'][] = ['id' => $item->id, 'title' => $item->title, 'stage_id' => $item->stage_id, 'category' => $item->stage?->name, 'details' => $this->itemDetails($plan)[$item->id] ?? null];
            }
            $ids = array_values(array_diff($active['purchased_ids'], [$item->id]));
            if ($item->state === PlanItem::STATE_BUY) {
                $ids[] = $item->id;
            }
            $active['purchased_ids'] = $ids;
            $this->store($plan, $active, 'shopping_active_session');
        });
    }

    public function finishSession(Plan $plan, array $data): array
    {
        return $plan->getConnection()->transaction(function () use ($plan, $data) {
            Plan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $field = 'shopping_visit_'.$data['session_id'];
            if ($finished = $this->sessionField($plan, $field)) {
                return $finished;
            }
            $active = $this->activeSession($plan);
            abort_unless($active && $active['id'] === $data['session_id'], 409, 'La sesión de compra cambió. Actualiza la lista.');
            $bought = array_values(array_filter($active['items'], fn (array $item) => in_array($item['id'], $active['purchased_ids'], true)));
            abort_if(empty($bought), 422, 'Marca al menos un producto en el carrito antes de terminar.');
            $visit = [...$active, 'completed_at' => now()->toIso8601String(), 'total' => number_format((float) $data['total'], 2, '.', ''), 'currency' => $data['currency'], 'note' => trim($data['note'] ?? ''), ...(! empty($data['payee_id']) ? ['payee_id' => (int) $data['payee_id']] : []), 'purchased_items' => $bought, 'remaining_items' => array_values(array_filter($active['items'], fn (array $item) => ! in_array($item['id'], $active['purchased_ids'], true)))];
            unset($visit['items'], $visit['purchased_ids']);
            $this->store($plan, $visit, $field);
            $this->store($plan, [], 'shopping_active_session');
            $current = $this->status($plan);
            if ($current['id']) {
                $this->complete($plan, $current['id']);
            }
            $this->clearReviewed($plan);
            $this->store($plan, [], 'shopping_item_details');
            PlanItem::query()->where('team_id', $plan->team_id)->where('plan_id', $plan->id)->update(['state' => PlanItem::STATE_PENDING, 'is_done' => false, 'commit_date' => null]);

            return $visit;
        });
    }

    public function confirm(Plan $plan, string $note, string $expectedVersion): array
    {
        $changed = false;
        $result = $plan->getConnection()->transaction(function () use ($plan, $note, $expectedVersion, &$changed) {
            Plan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $current = $this->status($plan);
            abort_unless(hash_equals($current['current_version'], $expectedVersion), 409, 'La lista cambió. Actualízala antes de confirmar.');
            if ($current['status'] === 'ready' && $current['note'] === trim($note)) {
                return $current;
            }
            $this->store($plan, ['id' => (string) Str::uuid(), 'note' => trim($note), 'reviewed_at' => now()->toIso8601String(), 'completed_at' => null, 'version' => $current['current_version']]);
            $changed = true;

            return $this->status($plan);
        });
        if ($changed) {
            User::find($plan->user_id)?->notify(new ShoppingListReady($plan->id, $plan->name, $result['id']));
        }

        return $result;
    }

    public function complete(Plan $plan, string $reviewId): array
    {
        return $plan->getConnection()->transaction(function () use ($plan, $reviewId) {
            Plan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $current = $this->status($plan);
            abort_unless($current['id'] === $reviewId && $current['reviewed_at'], 409, 'Hay una revisión más reciente. Actualiza la lista.');
            if ($current['status'] !== 'completed') {
                $this->clearReviewed($plan);
                $this->store($plan, [], 'shopping_item_details');
                $this->store($plan, ['id' => $current['id'], 'note' => $current['note'], 'reviewed_at' => $current['reviewed_at'], 'completed_at' => now()->toIso8601String(), 'version' => $current['version']]);
            }

            return $this->status($plan);
        });
    }
}
