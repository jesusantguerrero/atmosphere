<?php

namespace App\Http\Controllers;

use App\Domains\LogerProfile\Models\LogerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;

/**
 * Weekly routine (time-block planner). Built entirely on the existing Plan
 * module — no new table. The routine is a Plan of type ROUTINE with 7 stages
 * (one per weekday, order 0=Mon..6=Sun). Each block is a PlanItem whose
 * start/end/color/member live as item fields (FieldValue). The item's `order`
 * mirrors the start-time in minutes so blocks sort chronologically.
 */
class RoutineController extends Controller
{
    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public function index(Request $request, PlanService $planService): Response
    {
        $plan = $this->resolveRoutinePlan($request, $planService);

        return inertia('Routine/Index', [
            'plan' => $this->planPayload($plan),
            'members' => $this->members($request),
        ]);
    }

    /** Dashboard widget data: the block happening now and the next one today. */
    public function current(Request $request, PlanService $planService): JsonResponse
    {
        $plan = $this->resolveRoutinePlan($request, $planService);
        $dow = (int) date('N') - 1;               // 0=Mon .. 6=Sun
        $now = (int) date('G') * 60 + (int) date('i');

        $today = [];
        foreach ($plan->stages as $stage) {
            if ((int) $stage->order !== $dow) {
                continue;
            }
            foreach ($stage->items()->orderBy('order')->get() as $item) {
                $b = $this->blockPayload($item, $dow);
                $today[] = [
                    'block' => $b,
                    's' => $this->toMinutes($b['start']),
                    'e' => $this->toMinutes($b['end']),
                ];
            }
        }
        usort($today, fn ($a, $b) => $a['s'] <=> $b['s']);

        $current = null;
        $next = null;
        foreach ($today as $row) {
            if ($row['s'] <= $now && $now < $row['e']) {
                $current = $row['block'];
            }
            if ($row['s'] > $now && $next === null) {
                $next = $row['block'];
            }
        }

        return response()->json(['current' => $current, 'next' => $next]);
    }

    public function storeBlock(Request $request, Plan $plan): JsonResponse
    {
        $this->guard($request, $plan);
        $data = $this->validateBlock($request);

        $stage = $plan->stages->firstWhere('order', $data['day']);
        if (! $stage) {
            return response()->json(['error' => 'Invalid day.'], 422);
        }

        $item = PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'user_id' => $request->user()->id,
            'stage_id' => $stage->id,
            'title' => trim($data['title']),
            'state' => PlanItem::STATE_PENDING,
            'order' => $this->toMinutes($data['start']),
        ]);
        $item->saveFields($this->blockFields($data));

        return response()->json($this->blockPayload($item->fresh(), $data['day']), 201);
    }

    public function updateBlock(Request $request, Plan $plan, PlanItem $item): JsonResponse
    {
        $this->guard($request, $plan);
        $this->ensureItemBelongsTo($plan, $item);
        $data = $this->validateBlock($request);

        $stage = $plan->stages->firstWhere('order', $data['day']);
        if (! $stage) {
            return response()->json(['error' => 'Invalid day.'], 422);
        }

        $item->update([
            'stage_id' => $stage->id,
            'title' => trim($data['title']),
            'order' => $this->toMinutes($data['start']),
        ]);
        // Clear then re-save: the Plan module's saveFields update-branch is a
        // no-op (checks isset on a model), so we avoid duplicate field rows.
        $item->fields()->delete();
        $item->saveFields($this->blockFields($data));

        return response()->json($this->blockPayload($item->fresh(), $data['day']));
    }

    public function destroyBlock(Request $request, Plan $plan, PlanItem $item): JsonResponse
    {
        $this->guard($request, $plan);
        $this->ensureItemBelongsTo($plan, $item);

        DB::transaction(function () use ($item) {
            $item->fields()->delete();
            $item->delete();
        });

        return response()->json(['deleted' => true]);
    }

    /**
     * Copy every block from one weekday to one or more target weekdays in a
     * single call. mode=replace wipes the target day first (default); append
     * keeps what's there. This is the killer of the repetitive-entry pain:
     * "make Wed like Mon" is one request, not N block creations.
     */
    public function copyDay(Request $request, Plan $plan): JsonResponse
    {
        $this->guard($request, $plan);
        $data = $request->validate([
            'from' => ['required', 'integer', 'min:0', 'max:6'],
            'to' => ['required', 'array', 'min:1'],
            'to.*' => ['integer', 'min:0', 'max:6'],
            'mode' => ['nullable', 'in:replace,append'],
        ]);

        $from = (int) $data['from'];
        $mode = $data['mode'] ?? 'replace';
        $targets = array_values(array_filter(
            array_unique(array_map('intval', $data['to'])),
            fn ($d) => $d !== $from                    // never copy a day onto itself
        ));

        $sourceStage = $plan->stages->firstWhere('order', $from);
        if (! $sourceStage) {
            return response()->json(['error' => 'Invalid source day.'], 422);
        }
        $sourceItems = $sourceStage->items()->orderBy('order')->get();

        $created = [];
        DB::transaction(function () use ($plan, $request, $targets, $mode, $sourceItems, &$created) {
            foreach ($targets as $day) {
                $stage = $plan->stages->firstWhere('order', $day);
                if (! $stage) {
                    continue;
                }
                if ($mode === 'replace') {
                    foreach ($stage->items()->get() as $existing) {
                        $existing->fields()->delete();
                        $existing->delete();
                    }
                }
                foreach ($sourceItems as $src) {
                    $f = $src->fields->pluck('value', 'field_name')->toArray();
                    $item = PlanItem::create([
                        'plan_id' => $plan->id,
                        'team_id' => $plan->team_id,
                        'user_id' => $request->user()->id,
                        'stage_id' => $stage->id,
                        'title' => $src->title,
                        'state' => PlanItem::STATE_PENDING,
                        'order' => $src->order,
                    ]);
                    $item->saveFields([
                        ['name' => 'start', 'value' => $f['start'] ?? '00:00'],
                        ['name' => 'end', 'value' => $f['end'] ?? '00:00'],
                        ['name' => 'color', 'value' => $f['color'] ?? '#6E9BE6'],
                        ['name' => 'member', 'value' => $f['member'] ?? ''],
                        ['name' => 'note', 'value' => $f['note'] ?? ''],
                    ]);
                    $created[] = $this->blockPayload($item->fresh(), $day);
                }
            }
        });

        // replaced tells the client which day columns to clear before merging.
        return response()->json([
            'blocks' => $created,
            'mode' => $mode,
            'replaced' => $mode === 'replace' ? $targets : [],
        ], 201);
    }

    /** Assign (or clear) a member on every block of one weekday in one call. */
    public function assignDay(Request $request, Plan $plan): JsonResponse
    {
        $this->guard($request, $plan);
        $data = $request->validate([
            'day' => ['required', 'integer', 'min:0', 'max:6'],
            'member_id' => ['nullable', 'integer'],
        ]);

        $stage = $plan->stages->firstWhere('order', (int) $data['day']);
        if (! $stage) {
            return response()->json(['error' => 'Invalid day.'], 422);
        }

        $value = isset($data['member_id']) && $data['member_id'] !== null
            ? (string) $data['member_id'] : '';

        $ids = [];
        foreach ($stage->items()->get() as $item) {
            // Replace only the member field, leaving start/end/color/note intact.
            $item->fields()->where('field_name', 'member')->delete();
            $item->saveFields([['name' => 'member', 'value' => $value]]);
            $ids[] = $item->id;
        }

        return response()->json([
            'ids' => $ids,
            'member_id' => $data['member_id'] ?? null,
        ]);
    }

    // ---- helpers ------------------------------------------------------------

    private function validateBlock(Request $request): array
    {
        return $request->validate([
            'day' => ['required', 'integer', 'min:0', 'max:6'],
            'title' => ['required', 'string', 'max:120'],
            'start' => ['required', 'string', 'max:5'],
            'end' => ['required', 'string', 'max:5'],
            'color' => ['nullable', 'string', 'max:20'],
            'member_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function blockFields(array $data): array
    {
        return [
            ['name' => 'start', 'value' => $data['start']],
            ['name' => 'end', 'value' => $data['end']],
            ['name' => 'color', 'value' => $data['color'] ?? '#6E9BE6'],
            ['name' => 'member', 'value' => (string) ($data['member_id'] ?? '')],
            ['name' => 'note', 'value' => (string) ($data['note'] ?? '')],
        ];
    }

    private function resolveRoutinePlan(Request $request, PlanService $planService): Plan
    {
        $user = $request->user();
        $plan = $planService->getPlanTypeModel($user->current_team_id, PlanTypes::ROUTINE);

        if (! $plan) {
            $plan = $planService->createPlanBoard($user->currentTeam, PlanTypes::ROUTINE, 'Weekly Routine');
            $plan->stages()->delete();
            foreach (self::DAYS as $i => $day) {
                $plan->stages()->create([
                    'user_id' => $user->id,
                    'team_id' => $plan->team_id,
                    'name' => $day,
                    'order' => $i,
                ]);
            }
            $plan = $plan->fresh();
        }

        return $plan;
    }

    private function members(Request $request): array
    {
        return LogerProfile::where('team_id', $request->user()->current_team_id)
            ->get(['id', 'name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function planPayload(Plan $plan): array
    {
        $blocks = [];
        foreach ($plan->stages as $stage) {
            $day = (int) $stage->order;
            foreach ($stage->items()->orderBy('order')->get() as $item) {
                $blocks[] = $this->blockPayload($item, $day);
            }
        }

        return [
            'id' => $plan->id,
            'blocks' => $blocks,
            'days' => self::DAYS,
        ];
    }

    private function blockPayload(PlanItem $item, int $day): array
    {
        $f = $item->fields->pluck('value', 'field_name')->toArray();

        return [
            'id' => $item->id,
            'day' => $day,
            'title' => $item->title,
            'start' => $f['start'] ?? '00:00',
            'end' => $f['end'] ?? '00:00',
            'color' => $f['color'] ?? '#6E9BE6',
            'member_id' => isset($f['member']) && $f['member'] !== '' ? (int) $f['member'] : null,
            'note' => $f['note'] ?? '',
        ];
    }

    private function toMinutes(string $hhmm): int
    {
        $parts = explode(':', trim($hhmm));
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);

        return $h * 60 + $m;
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
}
