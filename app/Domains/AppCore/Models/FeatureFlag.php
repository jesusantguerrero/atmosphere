<?php

namespace App\Domains\AppCore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A runtime-configurable feature flag. Persisted so admins can toggle,
 * create, and delete flags without a code deploy — the trade-off being
 * that consumers reference flags by string key rather than a typed const.
 *
 * State priority (highest wins):
 *   1. An explicit override for the scope entity (team / user)
 *   2. rollout_percentage (deterministic hash-bucket of scope id)
 *   3. enabled_by_default
 *
 * NOTE: `owen-it/laravel-auditing` is not installed in Loger (yet). If /
 * when you want a change trail on flags, run `composer require owen-it/
 * laravel-auditing`, then re-add `use AuditableTrait` and
 * `implements Auditable` here + on FeatureFlagOverride. All admin toggles
 * already flow through the models' save() so nothing else changes.
 */
class FeatureFlag extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'description',
        'scope',
        'category',
        'enabled_by_default',
        'rollout_percentage',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'enabled_by_default' => 'boolean',
            'rollout_percentage' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(FeatureFlagOverride::class);
    }

    /**
     * Convenience factory the service layer uses to build a rollout-bucket
     * decision. Passing the same $scopeIdentifier always lands in the same
     * bucket so users don't flap in/out as the same request replays.
     */
    public function isInRolloutBucket(string|int $scopeIdentifier): bool
    {
        if ($this->rollout_percentage <= 0) {
            return false;
        }

        if ($this->rollout_percentage >= 100) {
            return true;
        }

        // Deterministic 0-99 bucket derived from CRC32 of the composite key.
        $bucket = crc32("{$this->key}:{$scopeIdentifier}") % 100;

        return $bucket < $this->rollout_percentage;
    }
}
