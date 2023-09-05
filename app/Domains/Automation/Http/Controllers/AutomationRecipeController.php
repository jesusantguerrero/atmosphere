<?php

namespace App\Domains\Automation\Http\Controllers;

use App\Domains\Automation\Models\AutomationRecipe;
use App\Http\Controllers\Controller;



class AutomationRecipeController extends Controller
{
    public function index() {
        return AutomationRecipe::all();
    }
}
