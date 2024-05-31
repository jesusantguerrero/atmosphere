<?php

namespace App\Http\Controllers\Meal;

use App\Domains\Meal\Models\Meal;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Illuminate\Support\Facades\Redirect;
use App\Domains\Meal\Services\MealService;


class MealShoppingListController
{
    public function __construct(Meal $meal, private MealService $mealService) {}

    public function index(PlanService $service) {
        $user = request()->user();

        return inertia('Meals/ShoppingList', [
            'chores' =>  [$service->getPlanType($user->current_team_id, PlanTypes::SHOPPING_LIST, request())]
        ]);
    }

    public function store(Meal $meal)
    {
        $this->mealService->addToShoppingList($meal->ingredients->toArray());
        return Redirect::back();
    }

    public function update() {
        $items = request()->post('items');
        $this->mealService->addToShoppingList($items);
    }
}
