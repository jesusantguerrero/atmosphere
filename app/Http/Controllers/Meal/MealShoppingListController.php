<?php

namespace App\Http\Controllers\Meal;

use App\Domains\AppCore\Models\Planner;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealPlan;
use App\Domains\Meal\Services\MealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Response;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;

class MealShoppingListController
{
    public function __construct(Meal $meal, private MealService $mealService) {}

    public function index(PlanService $service): Response
    {
        $user = request()->user();

        return inertia('Meals/ShoppingList', [
            'chores' => [$service->getPlanType($user->current_team_id, PlanTypes::SHOPPING_LIST, request())],
            'profiles' => LogerProfile::where('team_id', $user->current_team_id)->get(['id', 'name']),
        ]);
    }

    public function store(Meal $meal): RedirectResponse
    {
        $this->mealService->addToShoppingList($meal->ingredients->toArray());

        return Redirect::back();
    }

    public function update(): void
    {
        $items = request()->post('items');
        $this->mealService->addToShoppingList($items);
    }

    public function generateFromMealPlan(Request $request, PlanService $planService): RedirectResponse
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $user = $request->user();
        $team = $user->currentTeam;

        $plans = Planner::whereTeamId($team->id)
            ->where(['dateable_type' => MealPlan::class])
            ->inDateFrame($request->start_date, $request->end_date)
            ->with(['dateable', 'dateable.meal', 'dateable.meal.ingredients'])
            ->get();

        $ingredients = MealService::getIngredients($plans);

        if (empty($ingredients)) {
            return Redirect::back()->with('flash', ['message' => 'No ingredients found in the selected date range.', 'type' => 'warning']);
        }

        $shoppingList = $planService->getPlanTypeModel($team->id, PlanTypes::SHOPPING_LIST);

        if (! $shoppingList) {
            $shoppingList = $planService->createPlanBoard($team, PlanTypes::SHOPPING_LIST, PlanTypes::SHOPPING_LIST->name);
        }

        $firstStage = $shoppingList->stages->first();
        $existingTitles = $firstStage->items()->pluck('title')->map(fn ($t) => strtolower($t))->toArray();

        $added = 0;
        foreach ($ingredients as $name => $ingredient) {
            if (! in_array(strtolower($name), $existingTitles)) {
                $shoppingList->addItem($user, [
                    'title' => $name,
                    'stage_id' => $firstStage->id,
                    'fields' => [],
                    'order' => 1,
                ]);
                $added++;
            }
        }

        return Redirect::route('meals.shoppingList')->with('flash', [
            'message' => "$added ingredient(s) added to your shopping list.",
            'type' => 'success',
        ]);
    }

    public function share(Request $request, PlanService $planService): JsonResponse
    {
        $user = $request->user();
        $shoppingList = $planService->getPlanTypeModel($user->current_team_id, PlanTypes::SHOPPING_LIST);

        if (! $shoppingList) {
            return response()->json(['error' => 'Shopping list not found.'], 404);
        }

        if (! $shoppingList->share_token) {
            $shoppingList->share_token = Str::uuid()->toString();
            $shoppingList->save();
        }

        $url = route('shared.list', $shoppingList->share_token);

        return response()->json(['url' => $url, 'token' => $shoppingList->share_token]);
    }

    public function unshare(Request $request, PlanService $planService): JsonResponse
    {
        $user = $request->user();
        $shoppingList = $planService->getPlanTypeModel($user->current_team_id, PlanTypes::SHOPPING_LIST);

        if (! $shoppingList) {
            return response()->json(['error' => 'Shopping list not found.'], 404);
        }

        $shoppingList->share_token = null;
        $shoppingList->save();

        return response()->json(['message' => 'Shopping list is no longer shared.']);
    }
}
