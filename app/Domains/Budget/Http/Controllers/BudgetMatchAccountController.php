<?php

namespace App\Domains\Budget\Http\Controllers;

use Exception;
use App\Http\Controllers\Controller;
use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMatchAccount;
use App\Domains\Budget\Services\BudgetFundService;
use App\Domains\Budget\Services\BudgetMatchService;

class BudgetMatchAccountController extends Controller
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

    public function store(BudgetMatchService $matchAccountService)
    {
        try {
            $postData = request()->post();
            $matchAccountService->add(request()->user(), $postData);
            return redirect()->back();
        }catch (Exception $e) {
            return back()->with('flash', [
                'banner' => $e->getMessage(),
            ]);
        }
    }

    public function update(Category $category, BudgetMatchAccount $budgetMatchAccount, BudgetMatchService $matchAccountService)
    {
        try {
            $postData = request()->post();
            $matchAccountService->update($category, $budgetMatchAccount, request()->user(), $postData);
            return redirect()->back();
        } catch (Exception $e) {
            return back()->with('flash', [
                'banner' => $e->getMessage(),
            ]);
        }
    }
}
