<?php

namespace App\Domains\Budget\Services;

use App\Domains\Budget\Data\CategoryData;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Events\BudgetAssigned;
use App\Helpers\RequestQueryHelper;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;

class BudgetCategoryService {
    function __construct() { }

    public function addTarget(Category $category, mixed $postData) {
        return $category->budget()->create(array_merge($postData, [
            "name" => $category->name ?? $category->display_id,
            "team_id" => $category->team_id
        ]));
    }

    public function updateTarget(Category $category,mixed $postData) {
        return $category->budget()->update(array_merge($postData, [
            "name" => $category->name ?? $category->display_id,
        ]));
    }

    public static function getSavings($teamId, $startDate, $endDate) {
        $startMonth = substr((string) $startDate, 0, 7);
        $endMonth = substr((string) $endDate, 0, 7);

        return DB::query()
        ->where("budget_targets.team_id", $teamId)
        ->whereIn('budget_targets.target_type', ['saving_balance'])
        ->whereRaw("date_format(month, '%Y-%m') <= '$endMonth'")
        ->from('budget_months')
        ->join('budget_targets', 'budget_targets.category_id', 'budget_months.category_id')
        ->sum(DB::raw("budgeted + activity"));
    }


    public function assignBudget(Category $category, string $month, mixed $postData) {
        $amount = (double) $postData['budgeted'];
        $type = $postData['type'] ?? 'budgeted';
        $shouldAggregate = $category->name === BudgetReservedNames::READY_TO_ASSIGN->value|| $type === 'movement';


        $budgetMonth = BudgetMonth::updateOrCreate([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $month,
            'name' => $month,
        ], [
            'user_id' => $category->user_id,
            'budgeted' => $shouldAggregate ? DB::raw("budgeted + $amount") : $amount,
        ]);

        BudgetAssigned::dispatch($budgetMonth, $postData);
        return $budgetMonth;
    }

    public function getBudgetInfo($category, string $month) {
        $yearMonth = substr((string) $month, 0, 7);
        $monthBudget = (new BudgetMonthService())->getMonthByCategory($category, $month);
        $budgeted = $monthBudget ? $monthBudget->budgeted : 0;

        if ($category->account_id) {
            $prevMonthLeftOver = $this->getPrevMonthFundedLeftOver($category, $yearMonth);
            $prevMonthPaymentsLeftOver = $this->getPrevMonthPaymentsLeftOver($category, $yearMonth);
            $funded = $monthBudget ? $monthBudget->funded_spending : 0;
            $monthPayment = $monthBudget ? $monthBudget->payments : 0;

            $monthBalance = Money::of($funded, $category->account->currency_code)->minus($monthPayment)->getAmount()->toFloat();
            $available = Money::of($prevMonthPaymentsLeftOver, 'USD')
                // ->plus($prevMonthLeftOver)
                // ->minus($monthBalance)
                ->getAmount()
                ->toFloat();
        } else {
            $monthBalance = (double) $category->getMonthBalance($yearMonth)->balance;
            $prevMonthLeftOver = $this->getPrevMonthLeftOver($category, $yearMonth);
            $available = Money::of($budgeted, 'USD')->plus($prevMonthLeftOver)->plus($monthBalance)->getAmount()->toFloat();
        }

        $data = [
            'budgeted' => $budgeted,
            'activity' => $monthBalance,
            'available' => $available,
            'prevMonthLeftOver' => $prevMonthLeftOver,
        ];

        // if ($this->id == 718) {
        //     dd($data);
        // }

        return $data;
    }

    public static function getBudgetSubcategories($teamId) {
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
        ->sum(DB::raw("budgeted + activity"));
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
        ->sum(DB::raw("funded_spending"));
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
        ->sum(DB::raw("funded_spending - payments"));
    }

    public static function getNextBudgetItems($teamId) {
       BudgetTarget::getNextTargets($teamId);
    }

    public static function withBudgetInfo(Category $category) {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [ $startDate ] = RequestQueryHelper::getFilterDates($filters);

        $month = $startDate ?? date('Y-m-01');
        return array_merge($category->toArray(), [ 'month' => $month ], $category->getBudgetInfo($month));
    }

    public function updateActivity(Category $category, string $month) {
        $monthDate = Carbon::createFromFormat("Y-m-d", $month);
        $transactions = 0;
        $activity = 0;

        if ($category->account) {
            $transactions = $category->account->getMonthBalance($monthDate->format('Y-m'))->balance;
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

        echo "{$category->name} updated to {$activity}" . PHP_EOL;
    }

    public function findByAccount(Account $account) {
       return Category::where([
        "team_id" => $account->team_id,
        "account_id" => $account->id
       ])->first();
    }

    public function updateFundedSpending(Category $category, string $month) {
        $monthDate = Carbon::createFromFormat("Y-m-d", $month);
        $transactions = 0;
        $activity = 0;

        if ($category->account) {
            $yearMonth = $monthDate->format('Y-m');
            $transactions = $category->account->getMonthFundedSpending($yearMonth)->balance;
            $payments = $category->account->getMonthPayments($yearMonth)->balance;

            BudgetMonth::updateOrCreate([
                'category_id' => $category->id,
                'team_id' => $category->team_id,
                'month' => $month,
                'name' => $month,
            ], [
                'user_id' => $category->user_id,
                'funded_spending' => ($transactions * -1) ?? 0,
                'payments' => $payments ?? 0
            ]);

            echo "{$category->name} updated to {$activity}" . PHP_EOL;
        }

    }

    public static function findOrCreateByName(CategoryData $params) {
        $category = Category::where(function($query) use ($params) {
           return $query->where('display_id', Str::lower(Str::slug($params->name, "_")))->orWhere('name', $params->name);
        })->where(function ($query) use ($params) {
            return $query->where('team_id', $params->team_id)->orWhere('team_id', 0);
        })->first();

        if (!$category) {
            $category = Category::create([
                'display_id' => Str::slug($params->name, "_"),
                'name' => $params->name,
                'parent_id' => $params->parent_id,
                'user_id' => $params->user_id,
                'team_id' => $params->team_id,
                'account_id' => $params->account_id,
                'depth' => $params->parent_id ? 1 : 0,
                'index' => 0,
                'resource_type' => $params->resource_type->value
            ]);

            return $category->id;
        } else if ( $params->parent_id && $category->parent_id != $params->parent_id) {
            $category->parent_id = $params->parent_id;
            $category->depth = $params->parent_id ? 1 : 0;
            $category->save();
        }
        return $category ? $category->id : null;
    }
}


