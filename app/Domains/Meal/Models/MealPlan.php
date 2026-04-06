<?php

namespace App\Domains\Meal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlan extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'user_id', 'name', 'date', 'meal_type_id', 'meal_id', 'menu_id'];

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }

    public function mealType(): BelongsTo
    {
        return $this->belongsTo(MealType::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(MealMenu::class, 'menu_id');
    }
}
