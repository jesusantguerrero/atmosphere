<?php

namespace App\Domains\Budget\Services;

use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Models\Setting;
use App\Models\Team;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Category;

class BudgetRolloverService
{
    private ?Team $team = null;

    private mixed $accounts = [];

    /**
     * Team primary currency, resolved once per team. Only its scale (number of
     * decimals) matters here: it drives the rounding of every Money operation.
     */
    private string $currencyCode = 'USD';

    public function __construct(private BudgetCategoryService $budgetCategoryService) {}

    /**
     * Closes `$month` for every category and seeds the next month's carry.
     *
     * Returns whether the carry written into the next month differs from what
     * was already stored there. The next month reads nothing else from this
     * one, so an unchanged carry means every later month already holds the
     * right numbers.
     */
    public function rollMonth($teamId, $month, $categories = null): bool
    {
        $this->currencyCode = $this->resolveTeamCurrency($teamId);
        $nextMonth = Carbon::createFromFormat('Y-m-d', $month)->addMonthsWithNoOverflow(1)->format('Y-m-d');
        $storedCarry = $this->carryInto($teamId, $nextMonth);

        if (! $categories) {
            $categories = Category::where([
                'team_id' => $teamId,
            ])
                ->whereNot('name', BudgetReservedNames::READY_TO_ASSIGN->value)
                ->get();
        }
        $overspending = 0;
        $fundedFromBudgets = 0;
        $overspendingCategories = [];

        foreach ($categories as $category) {
            if ($category->account_id) {
                $fundedFromBudgets += $this->budgetCategoryService->updateFundedSpending($category, $month);
            }
            $available = $this->getAvailableInMonth($category, $month);
            $this->setMonthBudget($category, $month, $available);
            if ($available < 0) {
                $overspending += abs($available);
                $overspendingCategories[] = $category->display_id;
            }
        }

        $this->moveReadyToAssign($teamId, $month, $overspending, $fundedFromBudgets);

        return $storedCarry != $this->carryInto($teamId, $nextMonth);
    }

    /**
     * Everything a month inherits from the previous one, keyed by category.
     * Amounts are normalized so decimal strings from the DB compare equal to
     * the floats the rollover writes.
     *
     * @return array<int, array{left: float, moved: float, overspending: float}>
     */
    private function carryInto(int $teamId, string $month): array
    {
        return BudgetMonth::where(['team_id' => $teamId, 'month' => $month])
            ->get(['category_id', 'left_from_last_month', 'moved_from_last_month', 'overspending_previous_month'])
            ->mapWithKeys(fn (BudgetMonth $row) => [$row->category_id => [
                'left' => round((float) $row->left_from_last_month, 6),
                'moved' => round((float) $row->moved_from_last_month, 6),
                'overspending' => round((float) $row->overspending_previous_month, 6),
            ]])
            ->all();
    }

    private function getAvailableInMonth($category, $month)
    {
        $activity = (new BudgetCategoryService($category))->getCategoryActivity($category, $month);

        $budgetMonth = BudgetMonth::where([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $month,
            'name' => $month,
        ])->first();

        if (! $budgetMonth) {
            return;
        }

        if ($budgetMonth->category->account_id) {
            $available = Money::of($budgetMonth->left_from_last_month, $category->account->currency_code, null, RoundingMode::HalfUp)
                ->plus($budgetMonth->budgeted, RoundingMode::HalfUp)
                ->plus($budgetMonth->funded_spending, RoundingMode::HalfUp)
                ->minus(($budgetMonth->payments), RoundingMode::HalfUp)
                ->getAmount()
                ->toFloat();

            $activity = Money::of($budgetMonth->funded_spending, $category->account->currency_code, null, RoundingMode::HalfUp)
                ->minus($budgetMonth->payments)
                ->getAmount()
                ->toFloat();
        } else {
            $available = Money::of($budgetMonth?->budgeted ?? 0, $this->currencyCode, null, RoundingMode::HalfUp)
                ->plus(($budgetMonth->left_from_last_month ?? 0), RoundingMode::HalfUp)
                ->plus($activity, RoundingMode::HalfUp)
                ->getAmount()
                ->toFloat();
        }

        // close current month
        $budgetMonth->update([
            'activity' => $activity,
            'available' => $available,
        ]);

        return $available;
    }

    private function setMonthBudget($category, $month, $available = 0)
    {
        $nextMonth = Carbon::createFromFormat('Y-m-d', $month)->addMonthsWithNoOverflow(1)->format('Y-m-d');
        BudgetMonth::updateOrCreate([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $nextMonth,
            'name' => $nextMonth,
        ], [
            'user_id' => $category->user_id,
            'left_from_last_month' => $available > 0 ? $available : 0,
        ]);
    }

    private function moveReadyToAssign($teamId, $month, $overspending = 0, $fundedFromBudgets = 0)
    {
        $readyToAssignCategory = Category::where([
            'name' => BudgetReservedNames::READY_TO_ASSIGN->value,
            'team_id' => $teamId,
        ])->first();

        $results = DB::table('budget_months')
            ->where([
                'budget_months.team_id' => $teamId,
                'month' => $month,
            ])->whereNot('category_id', $readyToAssignCategory->id)
            ->whereNotNull('categories.parent_id')
            ->join('categories', 'categories.id', 'budget_months.category_id')
            ->join(DB::raw('categories g'), 'g.id', 'categories.parent_id')
            ->selectRaw("
            coalesce(sum(budgeted), 0) as budgeted,
            coalesce(sum(activity), 0) as budgetsActivity,
            coalesce(sum(payments), 0) as payments,
            sum(coalesce(available, 0)) as available,
            sum(CASE WHEN available < 0 THEN available ELSE 0 END) as overspendingInMonth,
            group_concat(g.index, '.', categories.index, ':', categories.name, ':', available) as description,
            coalesce(sum(funded_spending), 0) as funded_spending
        ")
            ->orderBy(DB::raw('concat(g.index, ".", categories.index)'))
            ->groupBy('month')
            ->first();

        $budgetMonth = BudgetMonth::where([
            'category_id' => $readyToAssignCategory->id,
            'team_id' => $readyToAssignCategory->team_id,
            'month' => $month,
            'name' => $month,
        ])->first();

        // Rolling a month that was never opened for Ready to Assign (first roll
        // of a fresh month, or the previous month's roll never ran) leaves both
        // the row and the aggregate missing. Start from zero — the row is
        // created by the updateOrCreate below.
        $leftFromLastMonth = $budgetMonth?->left_from_last_month ?? 0;
        $budgeted = $results?->budgeted ?? 0;

        $inflow = (new BudgetCategoryService($readyToAssignCategory))->getCategoryInflow($readyToAssignCategory, $month);
        $TBB = $leftFromLastMonth + $inflow;

        $nextMonth = Carbon::createFromFormat('Y-m-d', $month)->addMonthsWithNoOverflow(1)->format('Y-m-d');
        $overspending = abs($results?->overspendingInMonth ?? 0);
        $leftover = $TBB - $budgeted;

        $available = Money::of($leftFromLastMonth, $this->currencyCode, null, RoundingMode::HalfUp)
            ->plus($budgeted, RoundingMode::HalfUp)
            ->plus($results?->funded_spending ?? 0, RoundingMode::HalfUp)
            ->minus(($results?->payments ?? 0), RoundingMode::HalfUp)
            ->getAmount()
            ->toFloat();

        if ($overspending > 0 && $leftover > 0) {
            $overspendingCopy = $overspending;
            $overspending = $overspending > $leftover ? $overspending - $leftover : 0;
            $leftover = $overspendingCopy >= $leftover ? 0 : $leftover - $overspendingCopy;
        }

        if ($leftover <= 0) {
            $leftover = $leftover - $overspending;
        }

        // Close current month

        $details = $this->team->balanceDetail(Carbon::createFromFormat('Y-m-d', $month)->endOfMonth()->format('Y-m-d'), $this->accounts);

        BudgetMonth::updateOrCreate([
            'category_id' => $readyToAssignCategory->id,
            'team_id' => $readyToAssignCategory->team_id,
            'month' => $month,
            'name' => $month,
        ], [
            'user_id' => $readyToAssignCategory->user_id,
            'budgeted' => $budgeted,
            'activity' => $inflow,
            'available' => $available,
            'funded_spending' => $results?->funded_spending ?? 0,
            'payments' => $results?->payments ?? 0,
            'accounts_balance' => collect($details)->sum('balance'),
            'meta_data' => $details,
        ]);

        //  Set left over to the next month
        BudgetMonth::updateOrCreate([
            'category_id' => $readyToAssignCategory->id,
            'team_id' => $readyToAssignCategory->team_id,
            'month' => $nextMonth,
            'name' => $nextMonth,
        ], [
            'user_id' => $readyToAssignCategory->user_id,
            'left_from_last_month' => $leftover,
            'moved_from_last_month' => ($results?->available ?? 0) + $leftover,
            'overspending_previous_month' => $overspending,
        ]);
    }

    private function resolveTeamCurrency(int $teamId): string
    {
        $code = strtoupper(trim((string) (Setting::getByTeam($teamId)['team_primary_currency_code'] ?? '')));

        return preg_match('/^[A-Z]{3}$/', $code) ? $code : 'USD';
    }

    /**
     * Rolls every calendar month between `$yearMonth` and the current one.
     *
     * Months are rolled whether or not they have transactions: the carry
     * into a month is written by rolling the one before it, so skipping a
     * quiet month would drop the carry for every month after it.
     *
     * @param  string  $yearMonth  `YYYY-MM`
     * @param  int|null  $limit  roll at most this many months from `$yearMonth`; the current month is always rolled
     */
    public function startFrom($teamId, $yearMonth, $limit = null)
    {
        $this->team = Team::find($teamId);
        $this->accounts = Account::getByDetailTypes($teamId, AccountDetailType::ALL_CASH)->pluck('id');

        $categories = Category::where([
            'team_id' => $teamId,
        ])
            ->whereNot('name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->get();

        $currentMonth = now()->format('Y-m');
        $months = collect(CarbonPeriod::create(
            min($yearMonth, $currentMonth).'-01',
            '1 month',
            max($yearMonth, $currentMonth).'-01',
        ))
            ->map(fn (CarbonInterface $date) => $date->format('Y-m'))
            ->when($limit, fn ($months) => $months->take($limit))
            ->push($currentMonth)
            ->unique()
            ->sort()
            ->values();
        $lastMonth = $months->last();

        // Once a month's carry comes out unchanged, every month in between
        // already holds the right numbers. The current month is still rolled:
        // it may never have been opened (first roll after a month change).
        $settled = false;
        foreach ($months as $month) {
            if ($settled && $month !== $lastMonth) {
                continue;
            }

            try {
                $settled = ! $this->rollMonth($teamId, $month.'-01', $categories);
            } catch (Exception $e) {
                $settled = false;
                Log::error('BudgetRolloverService::rollMonth failed', [
                    'team_id' => $teamId,
                    'month' => $month,
                    'exception' => $e,
                ]);
            }
        }
    }

    // transactions with more than 3 days prior to the las reconciled transaction are not imported
}
