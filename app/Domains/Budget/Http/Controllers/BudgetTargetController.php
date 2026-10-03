<?php

namespace App\Domains\Budget\Http\Controllers;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Services\BudgetTargetService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;

class BudgetTargetController extends Controller
{
    /**
     * Guard the numeric target fields (amount + loan terms) so a bad payload
     * can't persist a negative principal/rate or a non-numeric term. Kept
     * additive — only these keys are checked, so existing target types that
     * don't send loan fields are unaffected.
     */
    private function validatePayload(): void
    {
        request()->validate([
            'amount' => ['nullable', 'numeric'],
            'principal' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'term_months' => ['nullable', 'numeric', 'min:0', 'max:1200'],
            'loan_start_date' => ['nullable', 'date'],
        ]);
    }

    public function store(Category $category, BudgetTargetService $budgetTargetService)
    {
        $this->authorize('update', $category);
        $this->validatePayload();
        $postData = request()->post();
        $budgetTargetService->add($category, request()->user(), $postData);

        return redirect()->back();
    }

    public function update(Category $category, BudgetTarget $budgetTarget, BudgetTargetService $budgetTargetService)
    {
        $this->authorize('update', $category);
        $this->validatePayload();
        $postData = request()->post();
        $budgetTargetService->update($category, $budgetTarget, request()->user(), $postData);

        return redirect()->back();
    }

    public function complete(Category $category, BudgetTarget $budgetTarget, BudgetTargetService $budgetTargetService)
    {
        $this->authorize('update', $category);
        abort_unless((int) $budgetTarget->category_id === (int) $category->id, 404);
        $postData = Arr::except(request()->post(), ['team_id', 'user_id', 'category_id']);
        $budgetTargetService->complete($budgetTarget, $category, $postData);

        return redirect()->back();
    }
}
