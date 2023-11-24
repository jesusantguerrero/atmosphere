<?php

namespace App\Domains\Budget\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Services\BudgetTargetService;

class BudgetTargetController extends Controller
{
    public function store(Category $category, BudgetTargetService $budgetTargetService)
    {
        $postData = request()->post();
        $budgetTargetService->add($category, request()->user(), $postData);
        return redirect()->back();
    }

    public function update(Category $category, BudgetTarget $budgetTarget, BudgetTargetService $budgetTargetService)
    {
        $postData = request()->post();
        $budgetTargetService->update($category, $budgetTarget, request()->user(), $postData);
        return redirect()->back();
    }

    public function complete(Category $category, BudgetTarget $budgetTarget, BudgetTargetService $budgetTargetService)
    {
        $postData = request()->post();
        $budgetTargetService->complete($budgetTarget, $category, $postData);
        return redirect()->back();
    }
}
