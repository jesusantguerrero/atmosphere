<?php

namespace App\Http\Controllers;

use App\Events\ShoppingListItemUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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
        $plan = $this->resolveActivePlan($request, $planService);

        return inertia('ShoppingList/Index', [
            'plan' => $this->planPayload($plan),
            'lists' => $this->listsPayload($request->user()->current_team_id),
            'activeListId' => $plan->id,
            'shareUrl' => $plan->share_token ? route('shared.list', $plan->share_token) : null,
            'shareToken' => $plan->share_token,
            'mercureUrl' => $this->mercureSubscribeUrl($plan),
        ]);
    }

    /**
     * JSON payload for the right-side shopping widget — same data the inertia
     * page uses, minus the mercure URL the widget doesn't subscribe to.
     * Lazy-loaded on widget open so we don't pay this on every navigation.
     */
    public function current(Request $request, PlanService $planService): JsonResponse
    {
        $plan = $this->resolveActivePlan($request, $planService);

        return response()->json([
            'plan' => $this->planPayload($plan),
            'shareUrl' => $plan->share_token ? route('shared.list', $plan->share_token) : null,
            'shareToken' => $plan->share_token,
        ]);
    }

    /** All shopping lists for the current team (drives the header switcher). */
    public function lists(Request $request): JsonResponse
    {
        return response()->json([
            'lists' => $this->listsPayload($request->user()->current_team_id),
        ]);
    }

    /** Create a new empty named list and return it as the active one. */
    public function createList(Request $request, PlanService $planService): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $plan = $planService->createPlanBoard(
            $user->currentTeam,
            PlanTypes::SHOPPING_LIST,
            trim($data['name']),
        );

        return response()->json([
            'plan' => $this->planPayload($plan->fresh()),
            'lists' => $this->listsPayload($user->current_team_id),
            'activeListId' => $plan->id,
            'shareUrl' => $plan->share_token ? route('shared.list', $plan->share_token) : null,
            'shareToken' => $plan->share_token,
        ], 201);
    }

    /** Rename a list. */
    public function renameList(Request $request, Plan $plan): JsonResponse
    {
        $this->guard($request, $plan);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $plan->name = trim($data['name']);
        $plan->save();

        return response()->json([
            'id' => $plan->id,
            'name' => $plan->name,
            'lists' => $this->listsPayload($plan->team_id),
        ]);
    }

    /** Delete a list and its stages/items. Always keeps at least one list. */
    public function deleteList(Request $request, Plan $plan, PlanService $planService): JsonResponse
    {
        $this->guard($request, $plan);
        $teamId = $plan->team_id;

        DB::transaction(function () use ($plan) {
            foreach ($plan->stages as $stage) {
                $stage->items()->delete();
            }
            $plan->stages()->delete();
            $plan->delete();
        });

        if (Plan::where(['team_id' => $teamId, 'plan_type_name' => PlanTypes::SHOPPING_LIST])->count() === 0) {
            $planService->createPlanBoard($request->user()->currentTeam, PlanTypes::SHOPPING_LIST, 'Shopping List');
        }

        return response()->json([
            'deleted' => true,
            'lists' => $this->listsPayload($teamId),
        ]);
    }

    /** Add a category (stage) to a list. */
    public function addStage(Request $request, Plan $plan): JsonResponse
    {
        $this->guard($request, $plan);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $stage = $plan->stages()->create([
            'user_id' => $request->user()->id,
            'team_id' => $plan->team_id,
            'name' => trim($data['name']),
            'order' => ((int) $plan->stages()->max('order')) + 1,
        ]);

        return response()->json([
            'id' => $stage->id,
            'name' => $stage->name,
            'items' => [],
        ], 201);
    }

    /**
     * Create a list from a pasted text block. Lines starting with a bullet
     * (-, *, and similar) become items; other non-empty lines become category
     * headers; a leading cart-emoji line (or the `name` field) sets the name.
     */
    public function import(Request $request, PlanService $planService): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'text' => ['required', 'string'],
        ]);

        $user = $request->user();
        $parsed = $this->parseList($data['text']);
        $name = trim((string) ($data['name'] ?? '')) ?: ($parsed['name'] ?? 'Imported list');

        $plan = DB::transaction(function () use ($planService, $user, $name, $parsed) {
            $plan = $planService->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, $name);

            $sections = $parsed['sections'];
            $hasNamedSections = collect($sections)->contains(fn ($s) => $s['name'] !== null);

            if ($hasNamedSections) {
                $plan->stages()->delete();
                $order = 0;
                foreach ($sections as $section) {
                    $stage = $plan->stages()->create([
                        'user_id' => $user->id,
                        'team_id' => $plan->team_id,
                        'name' => $section['name'] ?? $name,
                        'order' => $order++,
                    ]);
                    $this->seedItems($plan, $stage->id, $user->id, $section['items']);
                }
            } else {
                $stage = $plan->stages()->first();
                if ($stage) {
                    $items = collect($sections)->flatMap(fn ($s) => $s['items'])->all();
                    $this->seedItems($plan, $stage->id, $user->id, $items);
                }
            }

            return $plan;
        });

        return response()->json([
            'plan' => $this->planPayload($plan->fresh()),
            'lists' => $this->listsPayload($user->current_team_id),
            'activeListId' => $plan->id,
            'shareUrl' => $plan->share_token ? route('shared.list', $plan->share_token) : null,
            'shareToken' => $plan->share_token,
        ], 201);
    }

    private function seedItems(Plan $plan, int $stageId, int $userId, array $titles): void
    {
        $order = 0;
        foreach ($titles as $title) {
            $title = trim($title);
            if ($title === '') {
                continue;
            }
            PlanItem::create([
                'plan_id' => $plan->id,
                'team_id' => $plan->team_id,
                'user_id' => $userId,
                'stage_id' => $stageId,
                'title' => $title,
                'state' => PlanItem::STATE_PENDING,
                'order' => $order++,
            ]);
        }
    }

    /**
     * @return array{name: ?string, sections: array<int, array{name: ?string, items: array<int, string>}>}
     */
    private function parseList(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $bullets = ['-', '*'];
        $name = null;
        $sections = [];
        $current = null;

        foreach ($lines as $raw) {
            $line = trim($raw);
            if ($line === '') {
                continue;
            }

            $isItem = false;
            $itemText = $line;
            foreach ($bullets as $bullet) {
                if (mb_substr($line, 0, 1) === $bullet) {
                    $isItem = true;
                    $itemText = trim(mb_substr($line, 1));
                    $itemText = preg_replace('/^\[[ xX]?\]\s*/u', '', $itemText);
                    break;
                }
            }

            if ($isItem) {
                if ($current === null) {
                    $current = ['name' => null, 'items' => []];
                }
                if ($itemText !== null && trim($itemText) !== '') {
                    $current['items'][] = trim($itemText);
                }
                continue;
            }

            if ($name === null && $current === null && mb_strpos($line, "\u{1F6D2}") !== false) {
                $name = trim(str_replace("\u{1F6D2}", '', $line));
                continue;
            }

            if ($current !== null) {
                $sections[] = $current;
            }
            $current = ['name' => $line, 'items' => []];
        }

        if ($current !== null) {
            $sections[] = $current;
        }

        return ['name' => $name, 'sections' => $sections];
    }

    private function listsPayload($teamId): array
    {
        return Plan::where([
            'team_id' => $teamId,
            'plan_type_name' => PlanTypes::SHOPPING_LIST,
        ])->orderBy('id')->get()->map(function (Plan $plan) {
            $items = $plan->stages->flatMap(fn ($s) => $s->items);

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'total' => $items->count(),
                'pending' => $items->where('state', PlanItem::STATE_PENDING)->count(),
                'shared' => (bool) $plan->share_token,
            ];
        })->values()->all();
    }


    private function resolveActivePlan(Request $request, PlanService $planService): Plan
    {
        $user = $request->user();

        $requestedId = $request->query('plan') ?? $request->input('plan');
        if ($requestedId) {
            $requested = Plan::where([
                'id' => $requestedId,
                'team_id' => $user->current_team_id,
                'plan_type_name' => PlanTypes::SHOPPING_LIST,
            ])->first();
            if ($requested) {
                return $requested;
            }
        }

        $plan = $planService->getPlanTypeModel($user->current_team_id, PlanTypes::SHOPPING_LIST);

        if (! $plan) {
            $plan = $planService->createPlanBoard(
                $user->currentTeam,
                PlanTypes::SHOPPING_LIST,
                PlanTypes::SHOPPING_LIST->name,
            );
        }

        return $plan;
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
            'stage_id' => ['nullable', 'integer'],
        ]);

        $stage = null;
        if (! empty($data['stage_id'])) {
            $stage = $plan->stages()->where('id', $data['stage_id'])->first();
        }
        $stage = $stage ?? $plan->stages->first();
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
            'stage_id' => $item->stage_id,
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
