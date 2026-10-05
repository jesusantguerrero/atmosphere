<?php

namespace App\Domains\Budget\Http\Controllers;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Services\BudgetTargetService;
use App\Http\Controllers\Controller;
use App\Http\Requests\BudgetTargetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class BudgetTargetController extends Controller
{
    public function store(Category $category, BudgetTargetService $budgetTargetService, BudgetTargetRequest $request): RedirectResponse
    {
        $this->authorize('update', $category);
        $postData = $request->post();
        $budgetTargetService->add($category, request()->user(), $postData);

        return redirect()->back();
    }

    public function update(Category $category, BudgetTarget $budgetTarget, BudgetTargetService $budgetTargetService, BudgetTargetRequest $request): RedirectResponse
    {
        $this->authorize('update', $category);
        abort_unless((int) $budgetTarget->category_id === (int) $category->id, 404);
        $postData = $request->post();
        $budgetTargetService->update($category, $budgetTarget, request()->user(), $postData);

        return redirect()->back();
    }

    public function complete(Category $category, BudgetTarget $budgetTarget, BudgetTargetService $budgetTargetService): RedirectResponse
    {
        $this->authorize('update', $category);
        abort_unless((int) $budgetTarget->category_id === (int) $category->id, 404);
        $postData = Arr::except(request()->post(), ['team_id', 'user_id', 'category_id']);
        $budgetTargetService->complete($budgetTarget, $category, $postData);

        return redirect()->back();
    }
}
