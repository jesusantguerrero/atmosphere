<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Budget\Exports\BudgetExport;
use App\Domains\Budget\Imports\BudgetImport;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Insane\Journal\Models\Core\Category;
use Maatwebsite\Excel\Facades\Excel;

class BudgetMonthController extends Controller
{
    public function assign(BudgetCategoryService $service, Category $category, $month )
    {
        $postData = request()->post();
        $monthBalance = $service->assignBudget($category, $month, $postData);
        BudgetMovement::registerMovement($monthBalance, $postData);
        return Redirect::back();
    }

    public function updateActivity(BudgetCategoryService $service, Category $category, $month)
    {
        $service->updateActivity($category, $month);
        return Redirect::back();
    }

    public function import(Request $request) {
        Excel::import(new BudgetImport($request->user()), $request->file('file'));
        return redirect()->back();
    }

    public function export() {
        $today = now()->format('Y-m-d');
        return Excel::download(new BudgetExport(auth()->user()->current_team_id), "budget_as_of_{$today}.xlsx");
    }
}
