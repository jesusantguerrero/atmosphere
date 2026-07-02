<?php

namespace App\Domains\Budget\Models;

use App\Domains\AppCore\Models\Category;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * SpendingIntent — "I plan to spend $X on this category this month."
 *
 * See migration 2026_07_01_224308 for the rationale. This model is
 * deliberately dumb: no derived state, no lifecycle hooks. All the
 * planning UI needs is upsert-by-scope and read-back-by-month.
 */
class SpendingIntent extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'category_id',
        'month',
        'amount',
        'notes',
    ];

    protected $casts = [
        'month' => 'date:Y-m-d',
        'amount' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
