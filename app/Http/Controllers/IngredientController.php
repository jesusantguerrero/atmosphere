<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Freesgen\Atmosphere\Http\InertiaController;

class IngredientController extends InertiaController
{
    public function __construct(Ingredient $ingredient)
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
