<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\MealPlan;
use Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;

class MealPlanController extends InertiaController
{
    public function __construct(MealPlan $mealPlan)
    {
        $this->model = $mealPlan;
        $this->templates = [
            "index" => 'Planner',
            "create" => 'PlannerCreate',
            "edit" => 'PlannerCreate'
        ];
        $this->searchable = ['name'];
        $this->validationRules = [];
        $this->includes = ['dateable'];
        $this->filters = [];
    }

    protected function getIndexProps(Request $request)
    {
        $queryParams = $request->query() ?? [];
        $queryParams['limit'] = $queryParams['limit'] ?? 50;

        return [
            'mealPlans' => $this->getModelQuery($request),
            'meals' =>function () use ($request) {
                return Meal::where([
                    'team_id' => $request->user()->current_team_id
                ])->get();
            }
        ];
    }
}
