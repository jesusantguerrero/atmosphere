<?php

namespace App\Http\Controllers;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealType;
use App\Domains\Meal\Services\MealService;
use App\Http\Resources\MealResource;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redirect;

class MealController extends InertiaController
{
    public function __construct(Meal $meal)
    {
        $this->model = $meal;
        $this->templates = [
            "index" => 'Meals/Index',
            "create" => 'Meals/Create',
            "edit" => 'Meals/Create'
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required'
        ];
        $this->includes = ['ingredients', 'mealType'];
        $this->filters = [
            'date' => date('Y-m-01')
        ];
    }

    protected function parser($resources) {
        return MealResource::collection($resources);
    }

    protected function getIndexProps(Request $request, $resources): array
    {
        return [
            "mealTypes" => MealType::where([
                "team_id" => $request->user()->current_team_id,
                "user_id" => $request->user()->current_team_id
            ])->get(),
        ];
    }

    protected function afterSave($postData, $resource): void
    {
        $resource->saveIngredients($postData['ingredients']);
    }

    public function addPlan(Request $request, MealService $mealService) {
        $postData = $this->getPostData($request);
        $mealService->addPlan($postData);

        return Redirect::back();
    }

    public function random(Request $request) {
        $meals = Meal::where([
            'team_id' => $request->user()->current_team_id
        ])->get();

        return count($meals) ? $meals->random(): null;
    }
}
