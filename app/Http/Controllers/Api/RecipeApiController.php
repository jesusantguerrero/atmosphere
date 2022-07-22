<?php

namespace App\Http\Controllers\Api;

use App\Models\Meal;

class RecipeApiController extends BaseController
{
    public function __construct()
    {
        $this->model = new Meal();
        $this->searchable = ['name'];
        $this->validationRules = [];
    }
}
