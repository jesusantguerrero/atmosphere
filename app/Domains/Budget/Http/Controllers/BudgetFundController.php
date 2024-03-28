<?php

namespace App\Domains\Budget\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Services\BudgetFundService;
use App\Domains\Budget\Services\BudgetTargetService;

class BudgetFundController extends Controller
{

    public function index(BudgetFundService $budgetFundService) {
        if (request()->get('json')) {
            return $budgetFundService->list(request()->user()->current_team_id);
        }
        return inertia("Finance/EmergencyFund/Index",
        []);
    }

    public function show() {
        return inertia("Finance/EmergencyFund/Index",
        []);
    }

    public function store(BudgetFundService $budgetFundService)
    {
        $postData = request()->post();
        $budgetFundService->add(request()->user(), $postData);
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
