<?php

namespace App\Http\Controllers\Meal;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealType;
use App\Domains\Meal\Services\MealService;
use App\Http\Resources\MealResource;
use App\Http\Resources\PlannedMealResource;
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

    public function __invoke()
    {
        $request = request();
        $startDate = $request->query('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $teamId = $request->user()->current_team_id;

        $plannedMeals = Planner::where([
            'team_id' => $teamId,
            'date' => date('Y-m-d')
        ])->with(['dateable', 'dateable.mealType'])->get();

        $weekPlan = Planner::whereTeamId($teamId)
        ->inDateFrame($startDate, $endDate)
        ->with(['dateable', 'dateable.mealType'])
        ->get();

        return inertia('Meals/Overview', [
            "sectionTitle" => "Meal overview",
            "mostLikedMeals" => Meal::where([
                "team_id" => $teamId
            ])->limit(3)->get(),
            'ingredients' => function () use ($weekPlan) {
                return MealService::getIngredients($weekPlan);
            },
            "mealTypes" => MealType::where([
                "team_id" => $request->user()->current_team_id,
                "user_id" => $request->user()->current_team_id
            ])->get(),
            "meals" => PlannedMealResource::collection($plannedMeals),
        ]);
    }

    protected function parser($resources) {
        return MealResource::collection($resources);
    }

    protected function getIndexProps(Request $request, $resources = null): array
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
        if ( isset($postData['ingredients'])) {
            $resource->saveIngredients($postData['ingredients']);
        }
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

        return count($meals) ? $meals->random(): 'Noting to show';
    }
}
