<?php

namespace App\Http\Controllers;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\Budget;
use App\Domains\Budget\Services\BudgetCategoryService;

class BudgetTargetController extends Controller
{
    public function store(Category $category)
    {
        $postData = request()->post();
        (new BudgetCategoryService($category))->addTarget($postData);
        return redirect()->back();
    }

    public function update(Category $category, Budget $target)
    {
        $postData = request()->post();
        $target->update($postData, [
            "name" => $category->name,
            "category_id" => $target->category_id
        ]);
        return redirect()->back();
    }
}
