<?php

namespace App\Http\Controllers;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealType;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;

class MealPlannerController extends InertiaController
{
    public function __construct(Planner $mealPlan)
    {
        $this->model = $mealPlan;
        $this->templates = [
            "index" => 'Meals/Planner',
            "create" => 'Meals/PlannerCreate',
            "edit" => 'Meals/PlannerCreate'
        ];
        $this->searchable = ['name'];
        $this->validationRules = [];
        $this->includes = ['dateable'];
        $this->filters = [];
        $this->resourceName = "mealPlans";
    }

    function getIngredients($plans) {
        $ingredients = [];
        foreach ($plans as $plan) {
            $mealIngredients = $plan->dateable->ingredients;
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

    protected function getIndexProps(Request $request, $resource = null)
    {
        $mode = $queryParams['mode'] ?? '';
        $teamId = Auth()->user()->current_team_id;

        return [
            'mode' => $mode,
            'ingredients' => function () use ($resource) {
                return $this->getIngredients($resource);
            },
            'meals' => function () use ($request) {
                return Meal::where([
                    'team_id' => $request->user()->current_team_id
                ])->get();
            },
            'mealTypes' => MealType::where('team_id', $teamId)->get()
        ];
    }
}
