<?php

namespace App\Http\Controllers;

use Accounts;
use App\Helpers\BudgetHelper;
use App\Libraries\GoogleService;
use App\Models\Budget;
use App\Models\Integrations\AutomationRecipe;
use App\Models\Integrations\AutomationService;
use App\Models\Integrations\AutomationTask;
use App\Models\Integrations\Integration;
use App\Models\MonthBudget;
use App\Models\Planner;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Insane\Journal\Helpers\CategoryHelper;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction as CoreTransaction;
use Laravel\Jetstream\Jetstream;

class DashboardController {
    public function index(Request $request) {
        $startDate = $request->query('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $teamId = $request->user()->current_team_id;


        $budget = MonthBudget::getMonthAssignments($teamId, $startDate);

        $transactions = Transaction::getExpenses($teamId, $startDate, $endDate);

        $drafts = Transaction::getDrafts($teamId);


        return Inertia::render('Dashboard', [
            "strings" => __('dashboard'),
            "meals" => Planner::where([
                'team_id' => $teamId,
                'date' => date('Y-m-d')
            ])->with('dateable')->get(),
            "budgetTotal" => $budget->sum('budgeted'),
            "budget" => [],
            "categories" => $teamId ? CategoryHelper::getSubcategories($teamId, ['expenses', 'incomes', 'liabilities']) : null,
            "accounts" => $teamId ? CategoryHelper::getAccounts($teamId, ['cash_and_bank', 'credit_card']) : null,
            "transactionTotal" => $transactions->sum('total'),
            "transactions" => $transactions->map(function ($transaction) {
                return Transaction::parser($transaction);
            }),
            "drafts" => $drafts->map(function ($transaction) {
                return Transaction::parser($transaction);
            }),
        ]);
    }

    public function finance(Request $request) {
        $startDate = $request->query('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $lastMonthStartDate = $request->query('startDate', Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'));
        $lastMonthEndDate = $request->query('endDate', Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'));
        $teamId = $request->user()->current_team_id;


        $budget = Budget::where([
            'team_id' => $teamId
        ])->with('account')->get();


        $planned = BudgetHelper::getPlannedTransactions($teamId);
        $subscriptions = BudgetHelper::getPlannedTransactions($teamId, 1);

        $transactions = Transaction::getExpenses($teamId,$startDate, $endDate);

        $incomes = Transaction::where([
            'team_id' => $teamId,
            'status' => 'verified'
        ])
        ->byCategories(['inflow'], $teamId)
        ->getByMonth($startDate, $endDate)
        ->sum('total');

        $lastMonthIncomes= Transaction::where([
            'team_id' => $teamId,
            'direction' => "DEPOSIT",
            'status' => 'verified'
        ])->getByMonth($lastMonthStartDate, $lastMonthEndDate)->sum('total');

        $lastMonthExpenses= Transaction::where([
            'team_id' => $teamId,
            'direction' => "WITHDRAW",
            'status' => 'verified'
        ])->getByMonth($lastMonthStartDate, $lastMonthEndDate)->sum('total');

        return Inertia::render('Finance', [
            "planned" => $planned,
            "subscriptions" => $subscriptions,
            "budgetTotal" => $budget->sum('amount'),
            "budget" => $budget->map(function ($budget) use($startDate, $endDate) {
               return Budget::dashboardParser($budget, $startDate, $endDate);
            }),
            "categories" => CategoryHelper::getSubcategories($teamId, ['expenses', 'incomes']),
            "accounts" => Account::where('team_id', $teamId)->byDetailTypes(
                ['cash', 'bank', 'cash_on_hand', 'savings', 'credit_card'])
                ->orderBy('index', )->get(),
            "transactionTotal" => $transactions->sum('total'),
            "lastMonthExpenses" => $lastMonthExpenses,
            "income" => $incomes,
            "lastMonthIncome" => $lastMonthIncomes,
            "transactions" => $transactions->map(function ($transaction) {
                return Transaction::parser($transaction);
            })->take(4),
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
