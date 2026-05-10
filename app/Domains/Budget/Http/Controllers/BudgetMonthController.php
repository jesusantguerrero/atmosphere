<?php

namespace App\Domains\Budget\Http\Controllers;

use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Data\BudgetMovementData;
use App\Domains\Budget\Exports\BudgetExport;
use App\Domains\Budget\Imports\BudgetImport;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Budget\Services\BudgetMovementService;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasEnrichedRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Insane\Journal\Models\Core\Category;
use Maatwebsite\Excel\Facades\Excel;

class BudgetMonthController extends Controller
{
    use HasEnrichedRequest;

    public function assign(BudgetMovementService $service, Category $category, string $month)
    {
        $isMovement = request()->post('type');
        $postData = $this->getPostData();
        $movement = null;

        if (! $isMovement && $postData && $postData['budgeted'] !== null) {
            $movement = $service->registerAssignment(new BudgetAssignData(
                $postData['team_id'],
                $postData['user_id'],
                $month,
                $category->id,
                $postData['budgeted'],
            ));
        } elseif ($isMovement) {
            $movement = $service->registerMovement(BudgetMovementData::from($this->getPostData()));
        }

        $redirect = Redirect::back();

        if ($movement) {
            $redirect->with('flash', [
                'type' => 'movement_created',
                'movement_id' => $movement->id,
            ]);
        }

        return $redirect;
    }

    public function updateActivity(BudgetCategoryService $service, Category $category, $month)
    {
        $service->updateActivity($category, $month);

        return Redirect::back();
    }

    public function copyFromPrevious(Request $request, BudgetMovementService $service, string $month)
    {
        $request->validate([
            'overwrite' => ['sometimes', 'boolean'],
        ]);

        $result = $service->copyFromPrevious(
            $request->user()->current_team_id,
            $request->user()->id,
            $month,
            (bool) $request->boolean('overwrite'),
        );

        return Redirect::back()->with('flash', [
            'type' => 'success',
            'message' => "Copied {$result['copied']} categories from previous month",
            'meta' => $result,
        ]);
    }

    public function split(Request $request, BudgetMovementService $service, Category $category, string $month)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'splits' => ['required', 'array', 'min:1'],
            'splits.*.destination_category_id' => ['required', 'integer', 'exists:categories,id'],
            'splits.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $service->registerSplit(
            $request->user()->current_team_id,
            $request->user()->id,
            $category->id,
            $data['date'],
            $data['splits'],
        );

        return Redirect::back();
    }

    public function destroyMovement(Request $request, BudgetMovementService $service, BudgetMovement $movement)
    {
        // Authorization: users can only undo movements that belong to a team they
        // have access to. `$movement->team_id` is set by registerMovement.
        if (! $request->user()->allTeams()->pluck('id')->contains($movement->team_id)) {
            abort(403);
        }

        $service->revertMovement($movement);

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

    public function exportCsv(Request $request)
    {
        $teamId = $request->user()->current_team_id;
        $month = $request->query('month');

        $filename = $month
            ? "budget_{$month}.csv"
            : 'budget_all_months.csv';

        return Excel::download(new BudgetExport($teamId, $month ?: null), $filename);
    }
}
