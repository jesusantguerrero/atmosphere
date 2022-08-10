<?php

namespace App\Http\Controllers;


use App\Libraries\GoogleService;
use App\Models\Category;
use App\Models\Integrations\AutomationRecipe;
use App\Models\Integrations\AutomationService;
use App\Models\Integrations\AutomationTask;
use App\Models\Integrations\Integration;
use App\Models\MonthBudget;
use App\Models\Planner;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Insane\Journal\Models\Core\Account;
use Laravel\Jetstream\Jetstream;

class DashboardController {
    public function index(Request $request) {
        $startDate = $request->query('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $teamId = $request->user()->current_team_id;


        $budget = MonthBudget::getMonthAssignments($teamId, $startDate);
        $transactionsTotal = Transaction::getExpensesTotal($teamId, $startDate, $endDate);
        $drafts = Transaction::getDrafts($teamId);
        $categories = Category::getBudgetSubcategories($teamId);

        return Inertia::render('Dashboard', [
            "sectionTitle" => "Dashboard",
            "strings" => __('dashboard'),
            "meals" => Planner::where([
                'team_id' => $teamId,
                'date' => date('Y-m-d')
            ])->with('dateable')->get(),
            "budgetTotal" => $budget->sum('budgeted'),
            "categories" => $categories,
            "accounts" => $teamId ? Account::getByDetailTypes($teamId) : [],
            "transactionTotal" => $transactionsTotal,
            "drafts" => $drafts->map(function ($transaction) {
                return Transaction::parser($transaction);
            }),
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
