<?php

namespace App\Http\Controllers\Meal;

use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealType;
use App\Domains\Meal\Services\MealService;
use App\Http\Resources\MealResource;
use App\Http\Resources\PlannedMealResource;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redirect;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;

class MealController extends InertiaController
{
    public function __construct(Meal $meal, private MealService $mealService)
    {
        $this->model = $meal;
        $this->templates = [
            'index' => 'Meals/Index',
            'create' => 'Meals/Create',
            'edit' => 'Meals/Create',
            'view' => 'Meals/View',
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required',
        ];
        $this->includes = ['ingredients', 'mealType'];
        $this->filters = [
            'date' => date('Y-m-01'),
        ];
    }

    public function __invoke(PlanService $service)
    {
        $request = request();
        $startDate = $request->query('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $teamId = $request->user()->current_team_id;

        $plannedMeals = $this->mealService->getMealSchedule($teamId, date('Y-m-d'));
        $weekPlan = $this->mealService->getMealScheduleInFrame($teamId, $startDate, $endDate);

        return inertia('Meals/Overview', [
            'sectionTitle' => 'Meal overview',
            'mostLikedMeals' => Meal::where([
                'team_id' => $teamId,
            ])->limit(3)->get(),
            'ingredients' => function () use ($weekPlan) {
                return MealService::getIngredients($weekPlan);
            },
            'mealTypes' => MealType::where([
                'team_id' => $teamId,
                'user_id' => $request->user()->id,
            ])->get(),
            'meals' => PlannedMealResource::collection($plannedMeals),
            'shoppingList' => $service->getPlanType($teamId, PlanTypes::SHOPPING_LIST, request()),
        ]);
    }

    protected function parser($resources)
    {
        return MealResource::collection($resources);
    }

    protected function getIndexProps(Request $request, $resources = null): array
    {
        return [
            'mealTypes' => MealType::where([
                'team_id' => $request->user()->current_team_id,
                'user_id' => $request->user()->current_team_id,
            ])->get(),
        ];
    }

    protected function afterSave($postData, $resource): void
    {
        if (isset($postData['ingredients'])) {
            $resource->saveIngredients($postData['ingredients']);
        }
    }

    public function addPlan(Request $request, MealService $mealService)
    {
        $postData = $this->getPostData($request);
        $mealService->addPlan($postData);

        return Redirect::back();
    }

    public function random(Request $request)
    {
        $teamMeals = Meal::where([
            'team_id' => $request->user()->current_team_id,
        ]);

        // FD-1: bias toward favorites when they exist. Falls back to all meals if
        // the user hasn't liked anything yet.
        $favorites = (clone $teamMeals)->favorites()->get();
        $pool = $favorites->isNotEmpty() ? $favorites : $teamMeals->get();

        return count($pool) ? $pool->random() : 'Noting to show';
    }

    public function show(Request $request, int $id)
    {
        return inertia($this->templates['view'], $this->getEditProps($request, $id));
    }

    /**
     * FD-1: toggle is_liked on a meal so it surfaces in the favorites filter
     * and biases the random meal generator.
     */
    public function toggleFavorite(Meal $meal): RedirectResponse
    {
        abort_unless((int) $meal->team_id === (int) request()->user()->current_team_id, 403);
        $meal->toggleFavorite();

        return back();
    }
}
