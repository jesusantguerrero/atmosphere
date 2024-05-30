<?php

namespace App\Domains\Meal\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\Product;
use Modules\Plan\Entities\PlanTypes;
use App\Domains\Meal\Models\MealPlan;
use Modules\Plan\Services\PlanService;
use App\Domains\AppCore\Models\Planner;

class MealService
{
    public function addPlan($mealsData)
    {
        foreach ($mealsData['meals'] as $mealData) {
            if (isset($mealData['id']) && !str_contains($mealData['id'],"new::")) {
                $meal = Meal::find($mealData['id']);
            } elseif (isset($mealData['name'])) {
                $meal = Meal::create([
                    'name' => str_replace("new::", "", $mealData['name']),
                    'meal_type_id' => $mealData['meal_type_id'],
                    'team_id' => $mealsData['team_id'],
                    'user_id' => $mealsData['user_id'],
                ]);
            }

            $plan = MealPlan::updateOrCreate([
                'date' => Carbon::parse($mealsData['date'])->setTimezone('UTC')->toDateTimeString(),
                'meal_type_id' => $mealData['meal_type_id'],
                'team_id' => $meal->team_id,
                'user_id' => $meal->user_id,
            ], [
                'meal_id' => $meal->id,
                'name' => $meal->name,
            ]);

            Planner::create(array_merge($mealData, [
                'dateable_type' => MealPlan::class,
                'dateable_id' => $plan->id,
                'date' => $plan->date,
                'team_id' => $plan->team_id,
                'user_id' => $plan->user_id,
            ]));
        }
    }

    public function getMealSchedule(int $teamId)
    {
        return Planner::where([
            'team_id' => $teamId,
            'date' => date('Y-m-d'),
            'dateable_type' => MealPlan::class,
        ])->with(['dateable', 'dateable.mealType'])->get();
    }

    public function getMealScheduleInFrame(int $teamId, $startDate, $endDate)
    {
        return Planner::whereTeamId($teamId)
            ->where(['dateable_type' => MealPlan::class])
            ->inDateFrame($startDate, $endDate)
            ->with(['dateable', 'dateable.mealType'])
            ->get();
    }

    public static function scheduleMealOnDate($mealId, $date, $payload)
    {
        $meal = Meal::find($mealId);

        $plan = MealPlan::create([
            'date' => Carbon::parse($date)->setTimezone('UTC')->toDateTimeString(),
            'meal_type_id' => $payload['meal_type_id'],
            'team_id' => $meal->team_id,
            'user_id' => $meal->user_id,
            'meal_id' => $meal->id,
            'name' => $meal->name,
        ]);

        Planner::create([
            'dateable_type' => MealPlan::class,
            'dateable_id' => $plan->id,
            'date' => $plan->date,
            'team_id' => $plan->team_id,
            'user_id' => $plan->user_id,
        ]);
    }

    public static function getIngredients($plans)
    {
        $ingredients = [];
        foreach ($plans as $plan) {
            $mealIngredients = $plan->dateable?->meal?->ingredients;
            if ($mealIngredients && count($mealIngredients)) {
                foreach ($mealIngredients as $product) {
                    if (array_key_exists($product->name, $ingredients)) {
                        $ingredients[$product->name] = array_merge($ingredients[$product->name], [
                            'quantity' => $ingredients[$product->name]['quantity']++,
                        ]);
                    } else {
                        $ingredients[$product->name] = [
                            'quantity' => $product->quantity,
                            'unit' => $product->unit,
                        ];
                    }
                }
            }
        }

        return $ingredients;
    }

    public function addIngredientLabel(Product $product, mixed $postData, User $user) {
        if (!str_contains($postData['label_id'], "new::")) {
            $label = $product->labels()->find($postData['label_id']);
        } else {
            $labelName = str_replace("new::", "", $postData['label_id']);
            $label = $product->labels()->create([
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
                'name' => $labelName,
                'label' => $labelName,
                'color' => $postData['color'] ?? "",
            ]);
        }

        return $label;
    }

    // shopping list
    public function addToShoppingList($ingredients = [])
    {
        $planService = new PlanService();
        $user = request()->user();
        $team = request()->user()->currentTeam;
        $shoppingList =  $planService->getPlanTypeModel($team->id, PlanTypes::SHOPPING_LIST, request());
        if (!$shoppingList) {
            $shoppingList = $planService->createPlanBoard($team, PlanTypes::SHOPPING_LIST, PlanTypes::SHOPPING_LIST->name);
        }

        foreach ($ingredients as $ingredient) {
            $shoppingList->addItem($user, [
                "title" => $ingredient["name"],
                "board_id" => null,
                "stage_id" => $shoppingList->stages->first()->id,
                "fields" => [
                    [
                        "field_id" => 73,
                        "field_name" => "owner"
                    ],
                    [
                        "field_id" => 74,
                        "field_name" => "status"
                    ],
                    [
                        "field_id" => 75,
                        "field_name" => "due_date"
                    ],
                    [
                        "field_id" => 76,
                        "field_name" => "priority"
                    ]
                ],
                "order" => 1
            ]);
        }

    }
}
