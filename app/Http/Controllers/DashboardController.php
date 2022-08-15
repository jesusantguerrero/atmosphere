<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlannedMealResource;
use App\Libraries\GoogleService;
use App\Models\Category;
use App\Models\Integrations\AutomationRecipe;
use App\Models\Integrations\AutomationService;
use App\Models\Integrations\AutomationTask;
use App\Models\Integrations\Integration;
use App\Models\BudgetMonth;
use App\Models\Planner;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Insane\Journal\Models\Core\Account;
use Laravel\Jetstream\Jetstream;

class DashboardController {
    public function index(Request $request) {
        $startDate = $request->query('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $teamId = $request->user()->current_team_id;


        $budget = BudgetMonth::getMonthAssignments($teamId, $startDate);
        $transactionsTotal = Transaction::getExpensesTotal($teamId, $startDate, $endDate);
        $categories = Category::getBudgetSubcategories($teamId);
        $plannedMeals = Planner::where([
            'team_id' => $teamId,
            'date' => date('Y-m-d')
        ])->with(['dateable', 'dateable.mealType'])->get();

        // dd(PlannedMealResource::collection($plannedMeals));

        return Inertia::render('Dashboard', [
            "sectionTitle" => "Dashboard",
            "strings" => __('dashboard'),
            "meals" => PlannedMealResource::collection($plannedMeals),
            "budgetTotal" => $budget->sum('budgeted'),
            "categories" => $categories,
            "accounts" => $teamId ? Account::getByDetailTypes($teamId) : [],
            "transactionTotal" => $transactionsTotal,
        ]);
    }

    public function integrations(Request $request) {
        $user = $request->user();

        return Jetstream::inertia()->render($request, 'Integrations/Index', [
            "services" => AutomationService::all(),
            "recipes" => AutomationRecipe::all(),
            'tasks' => AutomationTask::all(),
            "integrations" => Integration::where([
                'team_id' => $user->current_team_id,
                'user_id' => $user->id
            ])->with(['automations'])->get()
        ]);
    }

    public function google(Request $request)
    {
       return GoogleService::setTokens((object) $request->post('credentials'), $request->user()->id);
    }
}
