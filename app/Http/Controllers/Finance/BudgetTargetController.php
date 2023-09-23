<?php

namespace App\Http\Controllers\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Http\Controllers\Controller;

class BudgetTargetController extends Controller
{
    public function store(Category $category, BudgetCategoryService $service)
    {
        $postData = request()->post();
        $service->addTarget($category, $postData);

        return redirect()->back();
    }

    public function update(Category $category, BudgetTarget $budgetTarget)
    {
        $postData = request()->post();
        $budgetTarget->update(array_merge(
            $postData, [
                'team_id' => request()->user()->current_team_id,
                'name' => $category->name,
                'category_id' => $budgetTarget->category_id,
            ]));

        return redirect()->back();
    }
}
