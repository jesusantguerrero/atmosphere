<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryGroupCollection;
use App\Models\BudgetMovement;
use App\Models\Category;
use App\Models\Planner;
use App\Models\Transaction;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class BudgetMonthController extends Controller
{


    public function assign(Request $request, $categoryId, $month)
    {
        $category = Category::find($categoryId);
        $postData = $request->post();
        $monthBalance = $category->assignBudget($month, $postData);
        BudgetMovement::registerMovement($monthBalance, $postData);
        return Redirect::back();
    }
}
