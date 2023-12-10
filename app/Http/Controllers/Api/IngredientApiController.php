<?php

namespace App\Http\Controllers\Api;

use App\Domains\Meal\Models\Product;
use App\Domains\Meal\Services\MealService;

class IngredientApiController extends BaseController
{
    public function __construct()
    {
        $this->model = new Product();
        $this->searchable = ['name'];
        $this->validationRules = [];
        $this->includes = ['labels'];
    }

    public function addLabel($id, MealService $mealService)
    {
        $product = $this->model->find($id);    
        $label = $mealService->addIngredientLabel(
            $product, 
            request()->post(), 
            request()->user()
        );

        return response()->json(['label' => $label]);
    }
}
