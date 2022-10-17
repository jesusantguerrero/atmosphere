<?php

namespace App\Domains\Meal\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealPlan;
use Illuminate\Support\Carbon;

class MealService {
    public function addPlan($mealsData) {
        foreach ($mealsData['meals'] as $mealData) {
            if (isset($mealData['id']) && $mealData['id'] !== "new::{$mealData['name']}") {
                $meal = Meal::find($mealData['id']);
            } else if (isset($mealData['name'])) {
                $meal = Meal::create([
                    'name' => $mealData['name'],
                    'meal_type_id' => $mealData['meal_type_id'],
                    'team_id' => $mealsData['team_id'],
                    'user_id' => $mealsData['user_id']
                ]);
            }

            $plan = MealPlan::updateOrCreate([
                'date' => Carbon::parse($mealsData['date'])->setTimezone('UTC')->toDateTimeString(),
                'meal_type_id' => $mealData['meal_type_id'],
                'team_id' => $meal->team_id,
                'user_id' => $meal->user_id
            ], [
                'meal_id' => $meal->id,
                'name' => $meal->name
            ]);

            Planner::create(array_merge($mealData ,[
                'dateable_type' => MealPlan::class,
                'dateable_id' => $plan->id,
                'date' => $plan->date,
                'team_id' => $plan->team_id,
                'user_id' => $plan->user_id
            ]));
        }
    }

    public static function scheduleMealOnDate($mealId, $date, $payload) {
        $meal = Meal::find($mealId);

        $plan = MealPlan::create([
            'date' => Carbon::parse($date)->setTimezone('UTC')->toDateTimeString(),
            'meal_type_id' => $payload['meal_type_id'],
            'team_id' => $meal->team_id,
            'user_id' => $meal->user_id,
            'meal_id' => $meal->id,
            'name' => $meal->name
        ]);

        Planner::create([
            'dateable_type' => MealPlan::class,
            'dateable_id' => $plan->id,
            'date' => $plan->date,
            'team_id' => $plan->team_id,
            'user_id' => $plan->user_id
        ]);
    }

    public static function getIngredients($plans) {
        $ingredients = [];
        foreach ($plans as $plan) {
            $mealIngredients = $plan->dateable?->meal?->ingredients;
            if ($mealIngredients && count($mealIngredients)) {
                foreach ($mealIngredients as $product) {
                    if (array_key_exists($product->name, $ingredients)) {
                        $ingredients[$product->name] = array_merge($ingredients[$product->name], [
                            'quantity' => $ingredients[$product->name]['quantity']++
                        ]);
                    } else {
                        $ingredients[$product->name] = [
                            'quantity' => $product->quantity,
                            'unit' => $product->unit
                        ];
                    }
                }
            }
        }

        return $ingredients;
    }
}
