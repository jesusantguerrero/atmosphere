<?php

namespace App\Http\Controllers\Meal;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealType;
use App\Domains\Meal\Services\MealService;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;

class MealPlannerController extends InertiaController
{
    public function __construct(Planner $mealPlan)
    {
        $this->model = $mealPlan;
        $this->templates = [
            'index' => 'Meals/Planner',
            'create' => 'Meals/PlannerCreate',
            'edit' => 'Meals/PlannerCreate',
        ];
        $this->searchable = ['name'];
        $this->validationRules = [];
        $this->includes = ['dateable'];
        $this->filters = [];
        $this->resourceName = 'mealPlans';
    }

    protected function getIndexProps(Request $request, $resources = null): array
    {
        $mode = $request->query('mode') ?? '';
        $teamId = Auth()->user()->current_team_id;

        return [
            'mealTypes' => MealType::where('team_id', $teamId)->get(),
            'mode' => $mode,
            'ingredients' => function () use ($resources, $mode) {
                return $mode ? MealService::getIngredients($resources) : [];
            },
            'meals' => function () use ($request) {
                return Meal::where([
                    'team_id' => $request->user()->current_team_id,
                ])->get();
            },
        ];
    }
}
