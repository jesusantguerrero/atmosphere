<?php

namespace App\Domains\AppCore\Facades;

use App\Domains\AppCore\Models\FeatureFlagOverride;
use App\Domains\AppCore\Services\FeatureFlagService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * Static entrypoint to the FeatureFlagService.
 *
 *   Feature::active('trends-relationships');                  // global
 *   Feature::active('trends-relationships', $team);           // per-team
 *   Feature::activeForUser('trends-relationships', $user);    // user + team + global
 *   Feature::activateFor('trends-relationships', $team);      // admin turn on
 *   Feature::deactivateFor('trends-relationships', $team);    // admin turn off
 *
 * @method static bool active(string $key, ?Model $scope = null)
 * @method static bool activeForUser(string $key, ?\App\Models\User $user)
 * @method static FeatureFlagOverride activateFor(string $key, Model $scope, ?string $reason = null)
 * @method static FeatureFlagOverride deactivateFor(string $key, Model $scope, ?string $reason = null)
 * @method static void clearOverride(string $key, Model $scope)
 * @method static \App\Domains\AppCore\Models\FeatureFlag toggleGlobal(string $key, bool $enabled)
 * @method static void invalidateFor(string $key, ?Model $scope = null)
 * @method static void invalidateAll(string $key)
 */
class Feature extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FeatureFlagService::class;
    }
}
