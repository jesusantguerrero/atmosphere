<?php

namespace App\Domains\Budget\Http\Controllers;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Services\BudgetFundService;
use App\Domains\Budget\Services\BudgetTargetService;
use App\Http\Controllers\Controller;

class BudgetFundController extends Controller
{
    public function index(BudgetFundService $budgetFundService)
    {
        if (request()->get('json')) {
            return $budgetFundService->list(request()->user()->current_team_id);
        }

        $user = request()->user();
        $crossTeamCategoryOptions = $user->allTeams()->map(function ($team) {
            $children = $team->categoryGroups()
                ->with('subCategories')
                ->orderBy('index')
                ->get()
                ->flatMap(function ($group) {
                    return $group->subCategories->map(fn ($cat) => [
                        'label' => $cat->name,
                        'value' => $cat->id,
                        'key' => $cat->id,
                    ]);
                })
                ->values()
                ->all();

            return [
                'label' => $team->name,
                'type' => 'group',
                'key' => "team-{$team->id}",
                'children' => $children,
            ];
        })->values()->all();

        return inertia('Finance/EmergencyFund/Index', [
            'crossTeamCategoryOptions' => $crossTeamCategoryOptions,
        ]);
    }

    public function show()
    {
        return inertia('Finance/EmergencyFund/Index',
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
