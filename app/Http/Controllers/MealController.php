<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\Planner;
use Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redirect;

class MealController extends InertiaController
{
    public function __construct(Meal $meal)
    {
        $this->model = $meal;
        $this->templates = [
            "index" => 'MealPlanner',
            "create" => 'MealPlannerCreate',
            "edit" => 'MealPlannerCreate'
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required'
        ];
        $this->includes = ['ingredients'];
        $this->filters = [];
    }

    protected function afterSave($postData, $resource): void
    {
        $resource->saveIngredients($postData['ingredients']);
    }

    public function addPlan(Request $request) {
        $postData = $this->getPostData($request);
        foreach ($postData['meals'] as $meal) {
            if (!isset($meal['id'])) continue;
            Planner::create(array_merge($postData ,[
                'dateable_type' => Meal::class,
                'dateable_id' => $meal['id'],
                'date' => Carbon::parse($postData['date'])->setTimezone('UTC')->toDateTimeString()
            ]));
        }

        return Redirect::back();
    }

    public function random(Request $request) {
        return Meal::where([
            'team_id' => $request->user()->current_team_id
        ])->get()->random();
    }
}
