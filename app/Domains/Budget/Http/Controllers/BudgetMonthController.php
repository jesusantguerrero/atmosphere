<?php

namespace App\Domains\Budget\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Redirect;
use Insane\Journal\Models\Core\Category;
use App\Domains\Budget\Exports\BudgetExport;
use App\Domains\Budget\Imports\BudgetImport;
use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Data\BudgetMovementData;
use App\Http\Controllers\Traits\HasEnrichedRequest;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Budget\Services\BudgetMovementService;

class BudgetMonthController extends Controller
{
    use HasEnrichedRequest;

    public function assign(BudgetMovementService $service, Category $category, string $month)
    {
        $isMovement = request()->post('type');
        $postData = $this->getPostData();
        if (!$isMovement && $postData && $postData['budgeted'] != null) {
            $service->registerAssignment(new BudgetAssignData(
                $postData['team_id'],
                $postData['user_id'],
                $month,
                $category->id,
                $postData['budgeted'],
            ));
        } elseif ($isMovement) {
            $service->registerMovement(BudgetMovementData::from($this->getPostData()));
        }

        return Redirect::back();
    }

    public function updateActivity(BudgetCategoryService $service, Category $category, $month)
    {
        $service->updateActivity($category, $month);

        return Redirect::back();
    }

    public function import(Request $request)
    {
        Excel::import(new BudgetImport($request->user()), $request->file('file'));

        return redirect()->back();
    }

    public function export()
    {
        $today = now()->format('Y-m-d');

        return Excel::download(new BudgetExport(auth()->user()->current_team_id), "budget_as_of_{$today}.xlsx");
    }
}
