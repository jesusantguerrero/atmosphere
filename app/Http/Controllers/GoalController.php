<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use Atmosphere\Http\InertiaController;

class GoalController extends InertiaController
{
    public function __construct(Goal $goal)
    {
        $this->model = $goal;
        $this->templates = [
            "index" => 'Goal',
            "create" => 'GoalCreate',
            "edit" => 'GoalCreate'
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required'
        ];
        $this->filters = [];
    }
}
