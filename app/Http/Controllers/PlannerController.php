<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\MealType;
use App\Models\Planner;
use Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlannerController extends InertiaController
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

    protected function getIndexProps(Request $request)
    {
        $queryParams = $request->query() ?? [];
        $queryParams['limit'] = $queryParams['limit'] ?? 50;
        $plans = $this->getModelQuery($request);
        $mode = $queryParams['mode'] ?? '';
        $teamId = Auth()->user()->current_team_id;
        return [
            'mealPlans' => $plans,
            'mode' => $mode,
            'ingredients' => function () use ($plans) {
                return $this->getIngredients($plans);
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
