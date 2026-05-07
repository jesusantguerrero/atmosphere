<?php

namespace App\Http\Controllers\Meal;

use App\Events\ShoppingListItemUpdated;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;

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

    /**
     * Set an item's 3-state directly (pending/buy/skip) or cycle through them
     * when no `state` is supplied. The payload supports both modes so the UI
     * can use a single tap to advance through states OR explicit state pickers.
     */
    public function toggleItem(Request $request, string $token, int $itemId): JsonResponse
    {
        $plan = $this->planByToken($token);
        $item = $this->itemForPlan($plan, $itemId);

        $next = $request->input('state');

        if ($next === null) {
            $item->cycleState();
        } else {
            $item->setState($next);
        }

        ShoppingListItemUpdated::dispatch($plan, $item->fresh(), 'updated');

        return response()->json([
            'id' => $item->id,
            'state' => $item->state,
            'is_done' => (bool) $item->is_done,
        ]);
    }

    /**
     * Add a new item to the shared list mid-trip — the partner spotting
     * something they forgot. Lands in the first stage by default.
     */
    public function addItem(Request $request, string $token): JsonResponse
    {
        $plan = $this->planByToken($token);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $stage = $plan->stages->first();
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

        ShoppingListItemUpdated::dispatch($plan, $item, 'created');

        return response()->json([
            'id' => $item->id,
            'title' => $item->title,
            'state' => $item->state,
        ], 201);
    }

    /**
     * Start a fresh "trip" without losing the master item registry: every
     * item resets to `pending`. Matches the wife's weekly workflow — same
     * list, fresh checkboxes.
     */
    public function resetTrip(string $token): JsonResponse
    {
        $plan = $this->planByToken($token);

        PlanItem::query()
            ->whereHas('stage', fn ($q) => $q->where('plan_id', $plan->id))
            ->update(['state' => PlanItem::STATE_PENDING, 'is_done' => false, 'commit_date' => null]);

        ShoppingListItemUpdated::dispatch($plan, null, 'reset');

        return response()->json(['reset' => true]);
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
