<?php

namespace App\Domains\Meal\Models;

use App\Events\AutomationEvent;
use App\Events\MealPlanLiked;
use App\Events\MealPlanUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use function Illuminate\Events\queueable;

class MealPlan extends Model
{
    use HasFactory;
    protected $fillable = ['team_id','user_id','name', 'date', 'meal_type_id', 'meal_id', 'menu_id', 'is_liked'];

    public function meal() {
        return $this->belongsTo(Meal::class);
    }

    public function mealType() {
        return $this->belongsTo(MealType::class);
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updated(function ($updatedModel) {
            if($updatedModel->isDirty('is_liked') && $updatedModel->is_liked) {
                AutomationEvent::dispatch($updatedModel->team_id, 'meal_plan_liked', $updatedModel->toArray());
            }
        });
    }
}
