<?php

namespace App\Domains\Meal\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class MealMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'description',
        'is_template',
    ];

    protected function casts(): array
    {
        return [
            'is_template' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function mealPlans(): HasMany
    {
        return $this->hasMany(MealPlan::class, 'menu_id');
    }

    public function duplicate(Carbon $targetStartDate): self
    {
        $sourcePlans = $this->mealPlans()->orderBy('date')->get();

        if ($sourcePlans->isEmpty()) {
            $clonedMenu = $this->replicate();
            $clonedMenu->save();

            return $clonedMenu;
        }

        $sourceStartDate = Carbon::parse($sourcePlans->first()->date)->startOfDay();

        $clonedMenu = $this->replicate();
        $clonedMenu->save();

        foreach ($sourcePlans as $sourcePlan) {
            $dayOffset = Carbon::parse($sourcePlan->date)->startOfDay()->diffInDays($sourceStartDate);
            $newDate = $targetStartDate->copy()->addDays($dayOffset);

            MealPlan::create([
                'team_id' => $sourcePlan->team_id,
                'user_id' => $sourcePlan->user_id,
                'meal_id' => $sourcePlan->meal_id,
                'meal_type_id' => $sourcePlan->meal_type_id,
                'date' => $newDate->toDateTimeString(),
                'name' => $sourcePlan->name,
                'menu_id' => $clonedMenu->id,
            ]);
        }

        return $clonedMenu;
    }
}
