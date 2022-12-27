<?php

namespace App\Http\Controllers\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Exports\BudgetExport;
use App\Domains\Budget\Imports\BudgetImport;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;

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

    public function import(Request $request) {
        Excel::import(new BudgetImport($request->user()), $request->file('file'));
        return redirect()->back();
    }

    public function export() {
        return Excel::download(new BudgetExport, 'budget.xlsx');
    }
}
