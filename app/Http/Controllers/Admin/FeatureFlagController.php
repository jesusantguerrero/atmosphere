<?php

namespace App\Http\Controllers\Admin;

use App\Domains\AppCore\Facades\Feature;
use App\Domains\AppCore\Models\FeatureFlag;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Feature Flag admin — create/edit/delete flags at runtime, toggle
 * global default, layer per-team / per-user overrides on top.
 *
 * All writes flow through the Feature facade so cache invalidation is
 * automatic and (when Auditing is later installed) the trail is
 * consistent with other admin surfaces.
 */
class FeatureFlagController extends Controller
{
    public function index(): Response
    {
        $flags = FeatureFlag::query()
            ->withCount('overrides')
            ->orderBy('category')
            ->orderBy('key')
            ->get(['id', 'key', 'name', 'description', 'scope', 'category',
                'enabled_by_default', 'rollout_percentage', 'created_at']);

        return Inertia::render('Admin/FeatureFlags/Index', [
            'flags' => $flags,
        ]);
    }

    public function show(FeatureFlag $featureFlag): Response
    {
        $featureFlag->load(['overrides' => function ($q) {
            $q->orderByDesc('created_at');
        }]);

        // Hydrate scope entities so the UI can show names, not just IDs.
        // Split by morph class to avoid n+1 lookups across mixed types.
        $teamIds = $featureFlag->overrides
            ->where('scope_type', Team::class)
            ->pluck('scope_id');
        $userIds = $featureFlag->overrides
            ->where('scope_type', User::class)
            ->pluck('scope_id');

        $teams = $teamIds->isEmpty()
            ? collect()
            : Team::query()->whereIn('id', $teamIds)->get(['id', 'name']);
        $users = $userIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $userIds)->get(['id', 'name', 'email']);

        $overrides = $featureFlag->overrides->map(function ($override) use ($teams, $users) {
            $entity = $override->scope_type === Team::class
                ? $teams->firstWhere('id', $override->scope_id)
                : $users->firstWhere('id', $override->scope_id);

            return [
                'id' => $override->id,
                'scope_type' => class_basename($override->scope_type),
                'scope_id' => $override->scope_id,
                'scope_label' => $entity?->name ?? "#{$override->scope_id}",
                'scope_email' => $entity->email ?? null,
                'enabled' => $override->enabled,
                'reason' => $override->reason,
                'created_at' => $override->created_at,
            ];
        });

        return Inertia::render('Admin/FeatureFlags/Show', [
            'flag' => [
                'id' => $featureFlag->id,
                'key' => $featureFlag->key,
                'name' => $featureFlag->name,
                'description' => $featureFlag->description,
                'scope' => $featureFlag->scope,
                'category' => $featureFlag->category,
                'enabled_by_default' => $featureFlag->enabled_by_default,
                'rollout_percentage' => $featureFlag->rollout_percentage,
                'metadata' => $featureFlag->metadata ?? (object) [],
                'created_at' => $featureFlag->created_at,
                'updated_at' => $featureFlag->updated_at,
            ],
            'overrides' => $overrides,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'unique:feature_flags,key',
                'regex:/^[a-z0-9-]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'scope' => ['required', Rule::in(['global', 'team', 'user'])],
            'category' => ['required', Rule::in(['experimental', 'gating', 'kill_switch', 'operations'])],
            'enabled_by_default' => ['boolean'],
        ]);

        $flag = FeatureFlag::query()->create($validated);

        return redirect()
            ->route('admin.feature-flags.show', $flag)
            ->with('flash', ['type' => 'success', 'message' => 'Feature flag created.']);
    }

    public function update(Request $request, FeatureFlag $featureFlag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['sometimes', Rule::in(['experimental', 'gating', 'kill_switch', 'operations'])],
            'enabled_by_default' => ['sometimes', 'boolean'],
            'rollout_percentage' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        // Route toggle-global through the facade so cache invalidation
        // fires consistently (updating the model directly would leave
        // stale cache entries for scoped queries).
        if (array_key_exists('enabled_by_default', $validated)) {
            Feature::toggleGlobal($featureFlag->key, (bool) $validated['enabled_by_default']);
            unset($validated['enabled_by_default']);
        }

        if (! empty($validated)) {
            $featureFlag->update($validated);
            Feature::invalidateAll($featureFlag->key);
        }

        return back()->with('flash', ['type' => 'success', 'message' => 'Flag updated.']);
    }

    public function destroy(FeatureFlag $featureFlag): RedirectResponse
    {
        $key = $featureFlag->key;
        $featureFlag->delete();
        Feature::invalidateAll($key);

        return redirect()
            ->route('admin.feature-flags.index')
            ->with('flash', ['type' => 'success', 'message' => 'Flag deleted.']);
    }

    /**
     * Attach or update an override. Payload: scope_type (Team|User),
     * scope_id, enabled, reason. Delegates to the facade so cache is
     * invalidated for that specific scope only.
     */
    public function storeOverride(Request $request, FeatureFlag $featureFlag): RedirectResponse
    {
        $validated = $request->validate([
            'scope_type' => ['required', Rule::in(['Team', 'User'])],
            'scope_id' => ['required', 'integer'],
            'enabled' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $scopeClass = $validated['scope_type'] === 'Team' ? Team::class : User::class;

        /** @var Team|User $scope */
        $scope = $scopeClass::query()->findOrFail($validated['scope_id']);

        if ($validated['enabled']) {
            Feature::activateFor($featureFlag->key, $scope, $validated['reason'] ?? null);
        } else {
            Feature::deactivateFor($featureFlag->key, $scope, $validated['reason'] ?? null);
        }

        return back()->with('flash', ['type' => 'success', 'message' => 'Override saved.']);
    }

    public function destroyOverride(Request $request, FeatureFlag $featureFlag, int $overrideId): RedirectResponse
    {
        $override = $featureFlag->overrides()->findOrFail($overrideId);

        $scopeClass = $override->scope_type;
        $scope = $scopeClass::query()->find($override->scope_id);

        if ($scope) {
            Feature::clearOverride($featureFlag->key, $scope);
        } else {
            // Scope entity was hard-deleted; drop the orphan override directly.
            $override->delete();
            Feature::invalidateAll($featureFlag->key);
        }

        return back()->with('flash', ['type' => 'success', 'message' => 'Override removed.']);
    }
}
