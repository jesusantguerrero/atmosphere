<?php

namespace App\Domains\AppCore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Explicit per-scope exception layered on top of a FeatureFlag's
 * enabled_by_default / rollout_percentage. Polymorphic — a scope is
 * either a Team or a User (extend the morph map in the service if new
 * scopes appear).
 *
 * See FeatureFlag for the note about re-enabling `owen-it/laravel-
 * auditing` once (if) the package gets installed.
 */
class FeatureFlagOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_flag_id',
        'scope_type',
        'scope_id',
        'enabled',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function featureFlag(): BelongsTo
    {
        return $this->belongsTo(FeatureFlag::class);
    }

    public function scope(): MorphTo
    {
        return $this->morphTo();
    }
}
