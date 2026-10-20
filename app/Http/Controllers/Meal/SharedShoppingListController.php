<?php

namespace App\Http\Controllers\Meal;

use App\Events\ShoppingListItemUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\SharedShoppingCategoryRequest;
use App\Http\Requests\SharedShoppingItemRequest;
use App\Http\Requests\SharedShoppingToggleRequest;
use App\Http\Requests\ShoppingListReviewRequest;
use App\Services\ShoppingListReviewService;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Symfony\Component\Mercure\Exception\RuntimeException as MercureException;

/**
 * Shared shopping-list endpoints — accessed by anyone holding the public
 * share-token URL (no auth). The token IS the auth: only the owner can mint
 * or revoke it. Every mutating action broadcasts via Mercure so the owner's
 * other tab and any partner phone update in real time.
 */
class SharedShoppingListController extends Controller
{
    public function show(string $token): Response
    {
        $plan = $this->planByToken($token);

        return inertia('ShoppingList/SharedShow', [
            'plan' => $this->planPayload($plan),
            'token' => $token,
            'mercureUrl' => $this->mercureSubscribeUrl($plan),
        ]);
    }

    public function review(string $token): JsonResponse
    {
        return response()->json(['plan' => $this->planPayload($this->planByToken($token))]);
    }

    public function confirmReview(ShoppingListReviewRequest $request, string $token, ShoppingListReviewService $reviews): JsonResponse
    {
        $plan = $this->planByToken($token);
        $review = $reviews->confirm($plan, (string) $request->validated('note', ''), $request->validated('current_version'));
        $this->notifyChange($request, $plan, null, 'reviewed');

        return response()->json(['review' => $review]);
    }

    /**
     * Set an item's 3-state directly (pending/buy/skip) or cycle through them
     * when no `state` is supplied. The payload supports both modes so the UI
     * can use a single tap to advance through states OR explicit state pickers.
     */
    public function toggleItem(SharedShoppingToggleRequest $request, string $token, int $itemId): JsonResponse
    {
        $plan = $this->planByToken($token);
        $item = $this->itemForPlan($plan, $itemId);

        $next = $request->input('state');

        if ($next === null) {
            $item->cycleState();
        } else {
            $item->setState($next);
        }

        if ($request->boolean('reviewed', true)) {
            app(ShoppingListReviewService::class)->markReviewed($plan, $item->fresh());
        } else {
            app(ShoppingListReviewService::class)->unmarkReviewed($plan, $item->fresh());
        }
        $this->notifyChange($request, $plan, $item->fresh(), 'updated');

        return response()->json([
            'id' => $item->id,
            'state' => $item->state,
            'is_done' => (bool) $item->is_done,
            'review' => app(ShoppingListReviewService::class)->status($plan),
        ]);
    }

    /**
     * Add a new item to the shared list mid-trip — the partner spotting
     * something they forgot. Lands in the first stage by default.
     */
    public function addItem(SharedShoppingItemRequest $request, string $token): JsonResponse
    {
        $plan = $this->planByToken($token);

        $data = $request->validated();
        $stage = isset($data['stage_id'])
            ? $plan->stages()->whereKey($data['stage_id'])->first()
            : $plan->stages->first();
        abort_if(isset($data['stage_id']) && ! $stage, 422, 'Selecciona una categoría de esta lista.');
        if (! empty($data['category_name'])) {
            $name = trim($data['category_name']);
            $stage = $plan->stages()->where('name', $name)->first() ?? $plan->stages()->create([
                'user_id' => $plan->user_id, 'team_id' => $plan->team_id,
                'name' => $name, 'order' => ((int) $plan->stages()->max('order')) + 1,
            ]);
        }
        if (! $stage) {
            return response()->json(['error' => 'List has no stages.'], 422);
        }

        $item = PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'user_id' => $plan->user_id,
            'stage_id' => $stage->id,
            'title' => trim($data['title']),
            'state' => PlanItem::STATE_PENDING,
            'order' => ((int) $stage->items()->max('order')) + 1,
        ]);

        app(ShoppingListReviewService::class)->markReviewed($plan, $item);
        $this->notifyChange($request, $plan, $item, 'created');

        return response()->json([
            'id' => $item->id,
            'title' => $item->title,
            'stage_id' => $stage->id,
            'stage' => ['id' => $stage->id, 'name' => $stage->name],
            'state' => $item->state,
            'review' => app(ShoppingListReviewService::class)->status($plan),
        ], 201);
    }

    /**
     * Start a fresh "trip" without losing the master item registry: every
     * item resets to `pending`. Matches the wife's weekly workflow — same
     * list, fresh checkboxes.
     */
    public function moveCategory(SharedShoppingCategoryRequest $request, string $token, int $itemId): JsonResponse
    {
        $plan = $this->planByToken($token);
        $item = $this->itemForPlan($plan, $itemId);
        $stage = $plan->stages()->whereKey($request->validated('stage_id'))->first();
        abort_unless($stage, 422, 'Selecciona una categoría de esta lista.');
        if ((int) $item->stage_id !== (int) $stage->id) {
            $item->update(['stage_id' => $stage->id, 'order' => ((int) $stage->items()->max('order')) + 1]);
        }
        if ($request->hasAny(['quantity', 'note'])) {
            app(ShoppingListReviewService::class)->saveItemDetails($plan, $item, (string) $request->validated('quantity', ''), (string) $request->validated('note', ''));
        }
        $this->notifyChange($request, $plan, $item->fresh(), 'updated');

        return response()->json(['plan' => $this->planPayload($plan->fresh())]);
    }

    public function resetTrip(Request $request, string $token): JsonResponse
    {
        $plan = $this->planByToken($token);

        PlanItem::query()
            ->whereHas('stage', fn ($q) => $q->where('plan_id', $plan->id))
            ->update(['state' => PlanItem::STATE_PENDING, 'is_done' => false, 'commit_date' => null]);

        app(ShoppingListReviewService::class)->clearReviewed($plan);
        $this->notifyChange($request, $plan, null, 'reset');

        return response()->json(['reset' => true, 'review' => app(ShoppingListReviewService::class)->status($plan)]);
    }

    /**
     * Same X-OneSignal-Subscription-Id extraction as the authed controller —
     * keeps the public mutating endpoints from echoing pushes back to the
     * partner's own device when they make the change.
     */
    private function notifyChange(Request $request, Plan $plan, ?PlanItem $item, string $action): void
    {
        try {
            ShoppingListItemUpdated::dispatch($plan, $item, $action, $this->actorSubscriptionId($request));
        } catch (BroadcastException|MercureException $exception) {
            report($exception);
        }
    }

    private function actorSubscriptionId(Request $request): ?string
    {
        $value = $request->header('X-OneSignal-Subscription-Id');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function planByToken(string $token): Plan
    {
        $plan = Plan::where('share_token', $token)->first();
        if (! $plan) {
            abort(404, 'Shopping list not found.');
        }

        return $plan;
    }

    private function itemForPlan(Plan $plan, int $itemId): PlanItem
    {
        $item = PlanItem::where('id', $itemId)
            ->whereHas('stage', fn ($q) => $q->where('plan_id', $plan->id))
            ->first();

        if (! $item) {
            abort(404, 'Item not found.');
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function planPayload(Plan $plan): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'review' => app(ShoppingListReviewService::class)->status($plan),
            'stages' => $plan->stages->map(fn ($stage) => [
                'id' => $stage->id,
                'name' => $stage->name,
                'items' => $stage->items()->orderBy('order')->get()->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'state' => $item->state ?? PlanItem::STATE_PENDING,
                    'is_done' => (bool) $item->is_done,
                    'order' => (int) $item->order,
                ]),
            ])->values(),
        ];
    }

    /**
     * Build the Mercure subscribe URL for this plan's share token. Returns
     * null when broadcasting isn't configured for Mercure so the frontend
     * gracefully degrades to no real-time updates.
     */
    private function mercureSubscribeUrl(Plan $plan): ?string
    {
        $hub = config('broadcasting.connections.mercure.hub_url') ?? env('MERCURE_URL');
        if (! $hub) {
            return null;
        }

        $topic = url("/shared/list/{$plan->share_token}");

        return rtrim($hub, '/').'?topic='.urlencode($topic);
    }
}
