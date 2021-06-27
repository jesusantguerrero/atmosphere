<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use Atmosphere\Http\InertiaController;

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
}
