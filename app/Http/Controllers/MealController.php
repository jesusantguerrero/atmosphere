<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\MealType;
use App\Models\Planner;
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
        $this->filters = [];
    }

    protected function getIndexProps(Request $request)
    {
        $queryParams = $request->query() ?? [];
        $queryParams['limit'] = $queryParams['limit'] ?? 50;
        $queryParams['date'] = $queryParams['date'] ?? date('Y-m-01');
        $teamId = $request->user()->current_team_id;

        return [
            "mealTypes" => MealType::where([
                "team_id" => $teamId,
                "user_id" => $request->user()->id
            ])->get(),
            "meals" => MealResource::collection($this->getModelQuery($request))
        ];
    }

    protected function afterSave($postData, $resource): void
    {
        $resource->saveIngredients($postData['ingredients']);
    }

    public function addPlan(Request $request) {
        $postData = $this->getPostData($request);
        foreach ($postData['meals'] as $mealData) {
            if (isset($mealData['id']) && $mealData['id'] !== 'new') {
                $meal = Meal::find($mealData['id']);
            } else if (isset($mealData['name'])) {
                $meal = Meal::create([
                    'name' => $mealData['name'],
                    'meal_type_id' => $mealData['meal_type_id'],
                    'team_id' => $request->user()->current_team_id,
                    'user_id' => $request->user()->id
                ]);
            }
            Planner::create(array_merge($postData ,[
                'dateable_type' => Meal::class,
                'dateable_id' => $meal->id,
                'date' => Carbon::parse($postData['date'])->setTimezone('UTC')->toDateTimeString()
            ]));
        }

        return Redirect::back();
    }

    public function random(Request $request) {
        $meals = Meal::where([
            'team_id' => $request->user()->current_team_id
        ])->get();

        return count($meals) ? $meals->random(): null;
    }
}
