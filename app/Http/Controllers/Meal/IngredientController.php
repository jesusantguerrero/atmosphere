<?php

namespace App\Http\Controllers\Meal;

use App\Domains\Meal\Models\Product;
use Freesgen\Atmosphere\Http\InertiaController;

class IngredientController extends InertiaController
{
    public function __construct(Product $ingredient)
    {
        $this->model = $ingredient;
        $this->templates = [
            "index" => 'Meals/Ingredients',
            "create" => 'Ingredients/Form',
            "edit" => 'Ingredients/Form',
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required'
        ];
        $this->includes = ['labels'];
        $this->filters = [];
    }
}
