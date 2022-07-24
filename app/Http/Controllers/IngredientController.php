<?php

namespace App\Http\Controllers;

use Freesgen\Atmosphere\Http\InertiaController;
use Insane\Journal\Models\Product\Product;

class IngredientController extends InertiaController
{
    public function __construct(Product $ingredient)
    {
        $this->model = $ingredient;
        $this->templates = [
            "index" => 'Ingredients/Index',
            "create" => 'Ingredients/Form',
            "edit" => 'Ingredients/Form',
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required'
        ];
        $this->filters = [];
    }
}
