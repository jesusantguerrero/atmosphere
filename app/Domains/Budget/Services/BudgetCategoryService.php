<?php

namespace App\Domains\Budget\Services;

use Brick\Money\Money;
use Illuminate\Support\Str;
use App\Events\BudgetAssigned;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Helpers\RequestQueryHelper;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use App\Domains\Budget\Data\CategoryData;
use App\Domains\Budget\Models\BudgetMonth;

class BudgetCategoryService
{
    public function __construct()
    {
    }

    public function getLastTransactionMonth($category)
    {
        return DB::query()
            ->where('budget_targets.team_id', $category->team_id)
            ->where('budget_targets.category_id', $category->id)
            ->where('activity', '<>', 0)
            ->whereIn('budget_targets.target_type', ['saving_balance'])
            ->from('budget_months')
            ->join('budget_targets', 'budget_targets.category_id', 'budget_months.category_id')
            ->orderByDesc('month')
            ->first();
    }

    public function getBudgetInfo($category, string $month)
    {
        $yearMonth = substr((string) $month, 0, 7);
        $monthBudget = (new BudgetMonthService())->getMonthByCategory($category, $yearMonth.'-01');
        $budgeted = $monthBudget ? $monthBudget->budgeted : 0;

        if ($category->account_id) {
            $funded = $monthBudget ? $monthBudget->funded_spending : 0;
            $monthPayment = $monthBudget ? $monthBudget->payments : 0;

            $monthBalance = Money::of($funded, $category->account->currency_code)->minus($monthPayment)->getAmount()->toFloat();
            $available = Money::of($monthBalance, 'USD')
                ->plus(($monthBudget->left_from_last_month * -1))
                ->getAmount()
                ->toFloat();
        } else {
            $monthBalance = (float) $category->getMonthBalance($yearMonth)->balance;

            $available = Money::of($budgeted, 'USD')
                        ->plus($monthBudget?->left_from_last_month ?? 0)
                        ->plus($monthBalance)
                        ->getAmount()
                        ->toFloat();

            if ($category->display_id == 'ready_to_assign') {
                $available = $monthBudget?->available;
                $monthBalance =  $monthBudget?->activity;
            }
        }

        $data = [
            'budgeted' => $monthBudget?->budgeted,
            'activity' => $monthBalance,
            'available' => $available,
            'payments' => $monthBudget?->payments ?? 0,
            'left_from_last_month' => $monthBudget?->left_from_last_month ?? 0,
            'funded_spending_previous_month' => 0,
            'funded_spending' => $monthBudget?->funded_spending ?? 0,
            'name' => $category->name,
            'month' => $yearMonth,
        ];
        return $data;
    }

    public function getBudgetData($category, string $month)
    {
        $yearMonth = substr((string) $month, 0, 7);
        $monthBudget = (new BudgetMonthService())->getMonthByCategory($category, $yearMonth.'-01');


        $data = [
            'budgeted' => $monthBudget?->budgeted,
            'activity' => $monthBudget?->activity,
            'available' => $monthBudget?->available,
            'payments' => $monthBudget?->payments ?? 0,
            'left_from_last_month' => $monthBudget?->left_from_last_month ?? 0,
            'funded_spending_previous_month' => 0,
            'funded_spending' => $monthBudget?->funded_spending ?? 0,
            'name' => $category->name,
            'month' => $yearMonth,
        ];

        return $data;
    }

    public static function getBudgetSubcategories($teamId)
    {
        return Category::where([
            'categories.team_id' => $teamId,
            'categories.resource_type' => 'transactions',
        ])
            ->whereNull('parent_id')
            ->orderBy('index')
            ->with('subCategories')
            ->get();
    }

    /**
     * Get the current balance.
     *
     * @return string
     */
    public function getPrevMonthLeftOver($category, $yearMonth)
    {
        return DB::query()
            ->select('*')
            ->where([
                'category_id' => $category->id,
            ])
            ->whereRaw("date_format(month, '%Y-%m') < '$yearMonth'")
            ->from('budget_months')
            ->sum(DB::raw('budgeted + activity'));
    }

    /**
     * Get the current balance.
     *
     * @return string
     */
    public function getPrevMonthFundedLeftOver($category, $yearMonth)
    {
        return DB::query()
            ->select('*')
            ->where([
                'category_id' => $category->id,
            ])
            ->whereRaw("date_format(month, '%Y-%m') < '$yearMonth'")
            ->from('budget_months')
            ->sum(DB::raw('funded_spending'));
    }

    /**
     * Get the current balance.
     *
     * @return string
     */
    public function getPrevMonthPaymentsLeftOver($category, $yearMonth)
    {
        return DB::query()
            ->select('*')
            ->where([
                'category_id' => $category->id,
            ])
            ->whereRaw("date_format(month, '%Y-%m') <= '$yearMonth'")
            ->from('budget_months')
            ->sum(DB::raw('funded_spending - payments'));
    }

    public static function withBudgetInfo(Category $category)
    {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate] = RequestQueryHelper::getFilterDates($filters);

        $month = $startDate ?? date('Y-m-01');

        return array_merge($category->toArray(), ['month' => $month], $category->getBudgetInfo($month));
    }

    public function updateActivity(Category $category, string $month)
    {
        $monthDate = Carbon::createFromFormat('Y-m-d', $month);
        $transactions = 0;
        $activity = 0;

        if ($category->account) {
            $transactions = $category->account->getMonthBalance($monthDate->format('Y-m'))->balance;
            echo "$category->name:$transactions";
        } else {
            $activity = $category->getMonthBalance($monthDate->format('Y-m'))?->balance;
        }

        BudgetMonth::updateOrCreate([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $month,
            'name' => $month,
        ], [
            'user_id' => $category->user_id,
            'activity' => ($activity + $transactions) ?? 0,
        ]);
    }

    public function updateMonthBalances(Category $category, string $month)
    {
        $monthBudgetInfo = $this->getBudgetInfo($category, $month);
        BudgetMonth::updateOrCreate([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $month,
            'name' => $month,
        ], [
            'available' => $monthBudgetInfo['available'],
            'payments' =>  $monthBudgetInfo['payments'],
            'left_from_last_month' => $monthBudgetInfo['left_from_last_month'],
            'funded_spending_previous_month' => 0,
            'funded_spending' => $monthBudgetInfo['funded_spending'],
        ]);
    }

    public function getCategoryActivity(Category $category, string $month)
    {
        $monthDate = Carbon::createFromFormat('Y-m-d', $month);
        $transactions = 0;
        $activity = 0;

        if ($category->account) {
            $transactions = $category->account->getMonthBalance($monthDate->format('Y-m'))->balance;
        } else {
            $activity = $category->getMonthBalance($monthDate->format('Y-m'))?->balance;
        }

        return  ($activity + $transactions) ?? 0;
    }

    public function findByAccount(Account $account)
    {
        return Category::where([
            'team_id' => $account->team_id,
            'account_id' => $account->id,
        ])->first();
    }

    public function updateFundedSpending(Category $category, string $month)
    {
        $monthDate = Carbon::createFromFormat('Y-m-d', $month);
        $transactions = 0;
        $activity = 0;
        $fromBudgets = 0;

        if ($category->account) {
            $yearMonth = $monthDate->format('Y-m');
            $transactions = $category->account->getMonthFundedSpending($yearMonth)->balance;
            $payments = $category->account->getMonthPayments($yearMonth)->balance;

            $fundedSpending = ($transactions * -1) ?? 0;

            BudgetMonth::updateOrCreate([
                'category_id' => $category->id,
                'team_id' => $category->team_id,
                'month' => $month,
                'name' => $month,
            ], [
                'user_id' => $category->user_id,
                'funded_spending' => $fundedSpending,
                'payments' => $payments ?? 0,
                'available' =>  DB::raw("($fundedSpending + available)  - $payments"),
            ]);
            $fromBudgets = $fundedSpending - $payments;
        }
        return $fromBudgets;
    }

    public static function findOrCreateByName(CategoryData $params)
    {
        $category = Category::where(function ($query) use ($params) {
            return $query->where('display_id', Str::lower(Str::slug($params->name, '_')))->orWhere('name', $params->name);
        })->where(function ($query) use ($params) {
            return $query->where('team_id', $params->team_id)->orWhere('team_id', 0);
        })->first();

        if (! $category) {
            $category = Category::create([
                'display_id' => Str::slug($params->name, '_'),
                'name' => $params->name,
                'parent_id' => $params->parent_id,
                'user_id' => $params->user_id,
                'team_id' => $params->team_id,
                'account_id' => $params->account_id,
                'depth' => $params->parent_id ? 1 : 0,
                'index' => 0,
                'resource_type' => $params->resource_type->value,
            ]);

            return $category->id;
        } elseif ($params->parent_id && $category->parent_id != $params->parent_id) {
            $category->parent_id = $params->parent_id;
            $category->depth = $params->parent_id ? 1 : 0;
            $category->save();
        }

        return $category ? $category->id : null;
    }
}
