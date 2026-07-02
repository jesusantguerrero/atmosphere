<?php

namespace App\Http\Controllers\Plan;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\SpendingIntent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * /plan — envelope-style monthly spending planner.
 *
 * Lets the user declare, per category per month, how much they intend
 * to spend, independent of budget_targets / budget_months. Sits above
 * the reconciliation pipeline: doesn't require verified transactions,
 * doesn't touch accounting.
 */
class SpendingPlanController
{
    /**
     * Render the plan page. Accepts ?month=YYYY-MM-DD (first-of-month)
     * or ?month=YYYY-MM. Defaults to the current month.
     */
    public function index(Request $request)
    {
        $teamId = $request->user()->current_team_id;
        $month = $this->parseMonth($request->query('month'));

        // Fetch category groups (parents) and their spending children.
        // We use `resource_type = transactions` and `parent_id IS NOT NULL`
        // to match the same universe the Budget page shows.
        $groups = Category::query()
            ->where('team_id', $teamId)
            ->whereNull('parent_id')
            ->where('resource_type', 'transactions')
            ->orderBy('index')
            ->with(['subCategories' => function ($q) use ($teamId) {
                $q->where('team_id', $teamId)
                    ->where('resource_type', 'transactions')
                    ->orderBy('index');
            }])
            ->get();

        $intents = SpendingIntent::query()
            ->where('team_id', $teamId)
            ->where('month', $month->format('Y-m-d'))
            ->get()
            ->keyBy('category_id');

        $payload = $groups->map(function ($group) use ($intents) {
            $children = $group->subCategories->map(function ($child) use ($intents) {
                $intent = $intents->get($child->id);

                return [
                    'category_id' => $child->id,
                    'name' => $child->name,
                    'amount' => $intent ? (float) $intent->amount : 0,
                    'notes' => $intent?->notes,
                ];
            });

            return [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'group_total' => (float) $children->sum('amount'),
                'items' => $children->values(),
            ];
        });

        return inertia('Plan/Index', [
            'sectionTitle' => 'Spending plan',
            'month' => $month->format('Y-m-d'),
            'monthLabel' => $month->format('F Y'),
            'groups' => $payload,
            'grandTotal' => (float) $payload->sum('group_total'),
        ]);
    }

    /**
     * Upsert a single intent. Called on debounced blur/change from the UI.
     */
    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'month' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $month = Carbon::parse($data['month'])->startOfMonth()->format('Y-m-d');

        $intent = SpendingIntent::updateOrCreate(
            [
                'team_id' => $user->current_team_id,
                'category_id' => $data['category_id'],
                'month' => $month,
            ],
            [
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'notes' => $data['notes'] ?? null,
            ]
        );

        return response()->json([
            'ok' => true,
            'intent' => $intent->only(['id', 'category_id', 'month', 'amount', 'notes']),
        ]);
    }

    /**
     * Copy the previous month's intents into the requested month.
     * Skips categories that already have an intent for the target month.
     */
    public function copyFromPrevious(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date'],
        ]);

        $user = $request->user();
        $target = Carbon::parse($data['month'])->startOfMonth();
        $source = $target->copy()->subMonth();

        $existing = SpendingIntent::query()
            ->where('team_id', $user->current_team_id)
            ->where('month', $target->format('Y-m-d'))
            ->pluck('category_id')
            ->all();

        $previous = SpendingIntent::query()
            ->where('team_id', $user->current_team_id)
            ->where('month', $source->format('Y-m-d'))
            ->whereNotIn('category_id', $existing)
            ->get();

        foreach ($previous as $prev) {
            SpendingIntent::create([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id,
                'category_id' => $prev->category_id,
                'month' => $target->format('Y-m-d'),
                'amount' => $prev->amount,
                'notes' => $prev->notes,
            ]);
        }

        return back();
    }

    private function parseMonth(?string $raw): Carbon
    {
        if (! $raw) {
            return Carbon::now()->startOfMonth();
        }
        try {
            return Carbon::parse($raw)->startOfMonth();
        } catch (\Throwable $e) {
            return Carbon::now()->startOfMonth();
        }
    }
}
