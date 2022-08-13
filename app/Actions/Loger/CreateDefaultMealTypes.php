<?php

namespace App\Actions\Loger;

use App\Models\MealType;

class CreateDefaultMealTypes
{
    public function setup($teamId, $userId) {
        $defaultMealTypes = [
            [
                "name" => "breakfast",
                "description" => "Breakfast",
            ],
            [
                "name" => "lunch",
                "description" => "Lunch",
            ],
            [
                "name" => "dinner",
                "description" => "Dinner",
            ],
            [
                "name" => "snack",
                "description" => "Snack",
            ],
        ];

        foreach ($defaultMealTypes as $mealType) {
            MealType::create(array_merge($mealType, [
                "team_id" => $teamId,
                "user_id" => $userId,
            ]));
        }
    }
}
