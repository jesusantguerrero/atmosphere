<?php

namespace App\Http\Controllers;

use App\Events\ShoppingListItemUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Response;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;

/**
 * Authed shopping-list endpoints. Mirrors the public share-token flow so the
 * same chat-style Vue component can drive both views: just point it at a
 * different base URL. Every mutation broadcasts via Mercure for real-time
 * sync between the owner's tabs and the partner's phone.
 *
 * The list itself is a `Plan` of type `SHOPPING_LIST` — reusing the existing
 * Plan module instead of building a parallel domain.
 */
class ShoppingListController extends Controller
{
    public function index(Request $request, PlanService $planService): Response
    {
        $user = $request->user();
        $plan = $planService->getPlanTypeModel($user->current_team_id, PlanTypes::SHOPPING_LIST);

        if (! $plan) {
            $plan = $planService->createPlanBoard(
                $user->currentTeam,
                PlanTypes::SHOPPING_LIST,
                PlanTypes::SHOPPING_LIST->name,
            );
        }

        return inertia('ShoppingList/Index', [
            'plan' => $this->planPayload($plan),
            'shareUrl' => $plan->share_token ? route('shared.list', $plan->share_token) : null,
            'shareToken' => $plan->share_token,
            'mercureUrl' => $this->mercureSubscribeUrl($plan),
        ]);
    }

    public function cycleItem(Request $request, Plan $plan, PlanItem $item): JsonResponse
    {
        $this->guard($request, $plan);
        $this->ensureItemBelongsTo($plan, $item);

        $next = $request->input('state');
        if ($next === null) {
            $item->cycleState();
        } else {
            $item->setState($next);
        }

        ShoppingListItemUpdated::dispatch($plan, $item->fresh(), 'updated', $this->actorSubscriptionId($request));

        return response()->json([
            'id' => $item->id,
            'state' => $item->state,
            'is_done' => (bool) $item->is_done,
        ]);
    }

    public function addItem(Request $request, Plan $plan): JsonResponse
    {
        $this->guard($request, $plan);

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
            'user_id' => $request->user()->id,
            'stage_id' => $stage->id,
            'title' => trim($data['title']),
            'state' => PlanItem::STATE_PENDING,
            'order' => ((int) $stage->items()->max('order')) + 1,
        ]);

        ShoppingListItemUpdated::dispatch($plan, $item, 'created', $this->actorSubscriptionId($request));

        return response()->json([
            'id' => $item->id,
            'title' => $item->title,
            'state' => $item->state,
            'order' => (int) $item->order,
        ], 201);
    }

    public function destroyItem(Request $request, Plan $plan, PlanItem $item): JsonResponse
    {
        $this->guard($request, $plan);
        $this->ensureItemBelongsTo($plan, $item);

        $snapshot = $item->replicate();
        $snapshot->id = $item->id;

        $item->delete();

        ShoppingListItemUpdated::dispatch($plan, $snapshot, 'deleted', $this->actorSubscriptionId($request));

        return response()->json(['deleted' => true]);
    }

    public function reset(Request $request, Plan $plan): JsonResponse
    {
        $this->guard($request, $plan);

        PlanItem::query()
            ->whereHas('stage', fn ($q) => $q->where('plan_id', $plan->id))
            ->update([
                'state' => PlanItem::STATE_PENDING,
                'is_done' => false,
                'commit_date' => null,
            ]);

        ShoppingListItemUpdated::dispatch($plan, null, 'reset', $this->actorSubscriptionId($request));

        return response()->json(['reset' => true]);
    }

    /**
     * Toggle public sharing on/off. Returns the share URL when enabling.
     */
    public function toggleShare(Request $request, Plan $plan): JsonResponse
    {
        $this->guard($request, $plan);

        if ($plan->share_token) {
            $plan->share_token = null;
            $plan->save();

            return response()->json(['shared' => false]);
        }

        $plan->share_token = (string) Str::uuid();
        $plan->save();

        return response()->json([
            'shared' => true,
            'token' => $plan->share_token,
            'url' => route('shared.list', $plan->share_token),
        ]);
    }

    /**
     * Extracts the OneSignal subscription ID the frontend sends with each
     * mutation so the listener can suppress an echo push back to the actor.
     */
    private function actorSubscriptionId(Request $request): ?string
    {
        $value = $request->header('X-OneSignal-Subscription-Id');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function guard(Request $request, Plan $plan): void
    {
        $user = $request->user();
        if (! $user || $plan->team_id !== $user->current_team_id) {
            abort(403);
        }
    }

    private function ensureItemBelongsTo(Plan $plan, PlanItem $item): void
    {
        if ($item->stage?->plan_id !== $plan->id) {
            abort(404);
        }
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

    private function mercureSubscribeUrl(Plan $plan): ?string
    {
        if (! $plan->share_token) {
            return null;
        }

        $hub = config('broadcasting.connections.mercure.hub_url') ?? env('MERCURE_URL');
        if (! $hub) {
            return null;
        }

        $topic = url("/shared/list/{$plan->share_token}");

        return rtrim($hub, '/').'?topic='.urlencode($topic);
    }
}
