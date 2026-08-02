<?php

namespace App\Domains\AppCore\Services;

use App\Domains\AppCore\Models\FeatureFlag;
use App\Domains\AppCore\Models\FeatureFlagOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Laravel\Jetstream\Team;

/**
 * FeatureFlagService — the single source of truth for flag resolution.
 *
 * Read path (fast): `active($key, $scope = null)` returns bool. Hot: every
 * page render can call this dozens of times, so results are cached for
 * FLAG_CACHE_TTL and keyed by (flag, scope type, scope id). Flag mutations
 * bump the cache by re-writing the key on save (invalidateFor).
 *
 * Write path (slow-ish, admin only): `activateFor`/`deactivateFor` create
 * or update an override; `toggleGlobal` flips enabled_by_default. Both go
 * through the model so `owen-it/laravel-auditing` records the who/when.
 */
class FeatureFlagService
{
    /**
     * Cache time-to-live for flag decisions. Kept short-ish so admin
     * changes propagate quickly even on machines that miss the invalidation
     * hook (e.g. queue workers with stale process memory).
     */
    public const CACHE_TTL_SECONDS = 300;

    /**
     * Resolve a flag for the given scope entity. Order of precedence:
     *   1. Explicit override → wins (short-circuit)
     *   2. Rollout bucket (deterministic hash of scope id)
     *   3. enabled_by_default
     *
     * Missing flag returns false — safer default, and lets callers query
     * upcoming flags before they exist in the DB without exploding.
     */
    public function active(string $key, ?Model $scope = null): bool
    {
        $cacheKey = $this->cacheKeyFor($key, $scope);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($key, $scope) {
            $flag = FeatureFlag::query()->where('key', $key)->first();

            if (! $flag) {
                return false;
            }

            if ($scope) {
                $override = $flag->overrides()
                    ->where('scope_type', $scope->getMorphClass())
                    ->where('scope_id', $scope->getKey())
                    ->first();

                if ($override) {
                    return $override->enabled;
                }
            }

            $scopeIdentifier = $scope?->getKey() ?? 'global';

            if ($flag->isInRolloutBucket($scopeIdentifier)) {
                return true;
            }

            return $flag->enabled_by_default;
        });
    }

    /**
     * Convenience helper: resolve for both the user AND their current team,
     * returning true if either scope has it active. Common case for UI gates
     * like "should this menu item show?" where either the individual or
     * team-wide toggle should be enough.
     */
    public function activeForUser(string $key, ?User $user): bool
    {
        if (! $user) {
            return $this->active($key);
        }

        if ($this->active($key, $user)) {
            return true;
        }

        $team = $user->currentTeam;

        if ($team && $this->active($key, $team)) {
            return true;
        }

        return $this->active($key);
    }

    /**
     * Explicitly enable a flag for a scope entity. Creates the override or
     * updates the existing one. Returns the persisted override so callers
     * can inspect audit metadata / reason.
     */
    public function activateFor(string $key, Model $scope, ?string $reason = null): FeatureFlagOverride
    {
        return $this->upsertOverride($key, $scope, true, $reason);
    }

    public function deactivateFor(string $key, Model $scope, ?string $reason = null): FeatureFlagOverride
    {
        return $this->upsertOverride($key, $scope, false, $reason);
    }

    /**
     * Remove an override so the flag falls back to rollout / default. Prefer
     * this over "activate then delete when rollout would give the same
     * answer" so the audit trail is clear.
     */
    public function clearOverride(string $key, Model $scope): void
    {
        $flag = FeatureFlag::query()->where('key', $key)->firstOrFail();

        $flag->overrides()
            ->where('scope_type', $scope->getMorphClass())
            ->where('scope_id', $scope->getKey())
            ->delete();

        $this->invalidateFor($key, $scope);
    }

    /**
     * Flip the global default. Admin UI's big red kill-switch calls this;
     * emits audit events on the flag itself.
     */
    public function toggleGlobal(string $key, bool $enabled): FeatureFlag
    {
        $flag = FeatureFlag::query()->where('key', $key)->firstOrFail();
        $flag->enabled_by_default = $enabled;
        $flag->save();

        $this->invalidateAll($key);

        return $flag;
    }

    /**
     * Clear cache entries touching a given flag/scope pair. Called from the
     * write paths above; also safe to call manually from tinker if the
     * cache gets weird.
     */
    public function invalidateFor(string $key, ?Model $scope = null): void
    {
        Cache::forget($this->cacheKeyFor($key, $scope));
    }

    /**
     * Blow away every cache entry for this flag. Used after global toggles
     * or bulk override changes where per-scope invalidation would miss a
     * warmed-up entry.
     */
    public function invalidateAll(string $key): void
    {
        // No tag support required — the flat forget-per-scope approach
        // keeps this compatible with the file/database cache stores that
        // the default Loger install uses.
        Cache::forget($this->cacheKeyFor($key, null));

        FeatureFlagOverride::query()
            ->whereHas('featureFlag', fn ($q) => $q->where('key', $key))
            ->get(['scope_type', 'scope_id'])
            ->each(function (FeatureFlagOverride $override) use ($key) {
                Cache::forget("feature_flag:{$key}:{$override->scope_type}:{$override->scope_id}");
            });
    }

    protected function upsertOverride(
        string $key,
        Model $scope,
        bool $enabled,
        ?string $reason,
    ): FeatureFlagOverride {
        $flag = FeatureFlag::query()->where('key', $key)->firstOrFail();

        $override = $flag->overrides()->updateOrCreate(
            [
                'scope_type' => $scope->getMorphClass(),
                'scope_id' => $scope->getKey(),
            ],
            [
                'enabled' => $enabled,
                'reason' => $reason,
            ],
        );

        $this->invalidateFor($key, $scope);

        return $override;
    }

    protected function cacheKeyFor(string $key, ?Model $scope): string
    {
        if (! $scope) {
            return "feature_flag:{$key}:global";
        }

        return "feature_flag:{$key}:{$scope->getMorphClass()}:{$scope->getKey()}";
    }
}
