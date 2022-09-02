<?php

namespace App\Http\Controllers;

use App\Domains\Budget\Models\Goal;
use Freesgen\Atmosphere\Http\InertiaController;

class GoalController extends InertiaController
{
    public function __construct(Goal $goal)
    {
        $this->model = $goal;
        $this->templates = [
            "index" => 'Finance/Goal',
            "create" => 'Finance/GoalCreate',
            "edit" => 'Finance/GoalCreate'
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required'
        ];
        $this->filters = [];
    }
}
