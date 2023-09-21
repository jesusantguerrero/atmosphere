<?php

namespace App\Domains\Meal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealPlan extends Model
{
    use HasFactory;

    protected $fillable = ['team_id', 'user_id', 'name', 'date', 'meal_type_id', 'meal_id', 'menu_id'];

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }

    public function mealType()
    {
        return $this->belongsTo(MealType::class);
    }
}
