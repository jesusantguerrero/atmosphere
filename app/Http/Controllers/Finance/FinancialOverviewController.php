<?php

namespace App\Http\Controllers\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetFund;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Setting;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Insane\Journal\Models\Core\AccountDetailType;

class FinancialOverviewController extends Controller
{
    const DEFAULT_EXCHANGE_RATE = 59.8;

    const SETTING_EXCHANGE_RATE = 'financial_overview_exchange_rate';

    const SETTING_BASE_CURRENCY = 'financial_overview_base_currency';

    const SETTING_QUOTE_CURRENCY = 'financial_overview_quote_currency';

    const SETTING_PINNED_GOALS = 'financial_overview_pinned_goals';

    const SETTING_GOAL_ACCOUNT_LINKS = 'financial_overview_goal_account_links';

    const SETTING_GOAL_CATEGORY_LINKS = 'financial_overview_goal_category_links';

    public function index(Request $request): Response
    {
        $user = $request->user();
        $teamId = $user->current_team_id;
        $teamIds = $user->allTeams()->pluck('id')->toArray();
        $settings = Setting::getByTeam($teamId);

        $exchangeRate = (float) ($settings[self::SETTING_EXCHANGE_RATE] ?? self::DEFAULT_EXCHANGE_RATE);
        $baseCurrency = $settings[self::SETTING_BASE_CURRENCY] ?? 'USD';
        $quoteCurrency = $settings[self::SETTING_QUOTE_CURRENCY] ?? 'DOP';
        $pinnedGoalIds = json_decode($settings[self::SETTING_PINNED_GOALS] ?? '[]', true) ?: [];
        $goalAccountLinks = json_decode($settings[self::SETTING_GOAL_ACCOUNT_LINKS] ?? '{}', true) ?: [];
        $goalCategoryLinks = json_decode($settings[self::SETTING_GOAL_CATEGORY_LINKS] ?? '{}', true) ?: [];

        $accounts = $this->buildAccountsData($teamIds, $exchangeRate, $baseCurrency, $quoteCurrency);
        $categoryIndex = $this->buildCategoryIndex($teamIds, $exchangeRate, $baseCurrency, $quoteCurrency);
        $pinnedGoals = $this->buildPinnedGoals($teamIds, $pinnedGoalIds, $accounts, $goalAccountLinks, $goalCategoryLinks, $categoryIndex);
        $availableGoals = $this->buildAllGoals($teamIds);
        $totals = $this->calculateTotals($accounts, $exchangeRate, $baseCurrency, $quoteCurrency);
        $bankBreakdown = $this->buildBankBreakdown($teamIds, $exchangeRate, $baseCurrency, $quoteCurrency);

        return inertia('Trends/FinancialOverview', [
            'accounts' => $accounts,
            'pinnedGoals' => $pinnedGoals,
            'availableGoals' => $availableGoals,
            'pinnedGoalIds' => $pinnedGoalIds,
            'goalAccountLinks' => (object) $goalAccountLinks,
            'goalCategoryLinks' => (object) $goalCategoryLinks,
            'availableCategories' => $this->groupCategoryIndexByTeam($categoryIndex),
            'bankBreakdown' => $bankBreakdown,
            'exchangeRate' => $exchangeRate,
            'baseCurrency' => $baseCurrency,
            'quoteCurrency' => $quoteCurrency,
            'totals' => $totals,
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'exchange_rate' => ['required', 'numeric', 'min:0'],
            'base_currency' => ['sometimes', 'string', 'size:3'],
            'quote_currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $teamId = $request->user()->current_team_id;
        $userId = $request->user()->id;

        $settingsData = [
            self::SETTING_EXCHANGE_RATE => $request->exchange_rate,
        ];

        if ($request->has('base_currency')) {
            $settingsData[self::SETTING_BASE_CURRENCY] = strtoupper($request->base_currency);
        }

        if ($request->has('quote_currency')) {
            $settingsData[self::SETTING_QUOTE_CURRENCY] = strtoupper($request->quote_currency);
        }

        Setting::storeBulk($settingsData, [
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);

        return response()->json(['success' => true]);
    }

    public function updatePinnedGoals(Request $request): JsonResponse
    {
        $request->validate([
            'pinned_goals' => ['required', 'array'],
            'pinned_goals.*' => ['string'],
        ]);

        $teamId = $request->user()->current_team_id;
        $userId = $request->user()->id;

        Setting::storeBulk([
            self::SETTING_PINNED_GOALS => json_encode($request->pinned_goals),
        ], [
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);

        return response()->json(['success' => true]);
    }

    public function updateGoalAccountLinks(Request $request): JsonResponse
    {
        $request->validate([
            'links' => ['present', 'array'],
            'links.*' => ['array'],
            'links.*.*' => ['integer'],
        ]);

        $teamId = $request->user()->current_team_id;
        $userId = $request->user()->id;

        $links = [];
        foreach ($request->input('links', []) as $goalId => $accountIds) {
            $clean = array_values(array_unique(array_map('intval', $accountIds)));
            if (! empty($clean)) {
                $links[$goalId] = $clean;
            }
        }

        Setting::storeBulk([
            self::SETTING_GOAL_ACCOUNT_LINKS => json_encode((object) $links),
        ], [
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);

        return response()->json(['success' => true]);
    }

    public function updateGoalCategoryLinks(Request $request): JsonResponse
    {
        $request->validate([
            'links' => ['present', 'array'],
            'links.*' => ['array'],
            'links.*.*' => ['integer'],
        ]);

        $user = $request->user();
        $teamId = $user->current_team_id;
        $userId = $user->id;

        $allowedCategoryIds = Category::query()
            ->whereIn('team_id', $user->allTeams()->pluck('id')->toArray())
            ->where('resource_type', 'transactions')
            ->whereNotNull('parent_id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $links = [];
        foreach ($request->input('links', []) as $goalId => $categoryIds) {
            $clean = array_values(array_unique(array_map('intval', $categoryIds)));
            $unauthorized = array_diff($clean, $allowedCategoryIds);

            if (! empty($unauthorized)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['links' => ['One or more category IDs do not belong to your teams.']],
                ], 422);
            }

            if (! empty($clean)) {
                $links[$goalId] = $clean;
            }
        }

        Setting::storeBulk([
            self::SETTING_GOAL_CATEGORY_LINKS => json_encode((object) $links),
        ], [
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);

        return response()->json(['success' => true]);
    }

    private function buildAccountsData(array $teamIds, float $exchangeRate, string $baseCurrency, string $quoteCurrency): array
    {
        $accounts = Account::whereIn('accounts.team_id', $teamIds)
            ->byDetailTypes(AccountDetailType::ALL_CASH)
            ->orderBy('accounts.index')
            ->with(['detailType', 'currencyBalances'])
            ->get();

        $grouped = [];

        foreach ($accounts as $account) {
            $detailTypeName = $account->detailType?->name ?? 'other';
            $primaryBalance = (float) $account->balance;
            $primaryCurrency = $account->getPrimaryCurrency();

            $baseBalance = $primaryCurrency === $baseCurrency ? $primaryBalance : null;
            $quoteBalance = $primaryCurrency === $quoteCurrency ? $primaryBalance : null;

            if ($account->is_multi_currency) {
                foreach ($account->currencyBalances as $currencyBalance) {
                    if ($currencyBalance->currency_code === $baseCurrency) {
                        $baseBalance = (float) $currencyBalance->balance;
                    } elseif ($currencyBalance->currency_code === $quoteCurrency) {
                        $quoteBalance = (float) $currencyBalance->balance;
                    }
                }
            }

            $baseInQuote = $baseBalance !== null ? $baseBalance * $exchangeRate : null;

            if (! isset($grouped[$detailTypeName])) {
                $grouped[$detailTypeName] = [
                    'group' => $detailTypeName,
                    'accounts' => [],
                    'subtotals' => ['base' => 0.0, 'quote' => 0.0, 'base_in_quote' => 0.0],
                ];
            }

            $grouped[$detailTypeName]['accounts'][] = [
                'id' => $account->id,
                'name' => $account->name,
                'bank_code' => $account->bank_code,
                'currency_code' => $primaryCurrency,
                'balance' => $primaryBalance,
                'base_balance' => $baseBalance,
                'quote_balance' => $quoteBalance,
                'base_in_quote' => $baseInQuote,
                'is_multi_currency' => $account->is_multi_currency,
            ];

            $grouped[$detailTypeName]['subtotals']['base'] += $baseBalance ?? 0;
            $grouped[$detailTypeName]['subtotals']['quote'] += $quoteBalance ?? 0;
            $grouped[$detailTypeName]['subtotals']['base_in_quote'] += $baseInQuote ?? 0;
        }

        return array_values($grouped);
    }

    private function buildBankBreakdown(array $teamIds, float $exchangeRate, string $baseCurrency, string $quoteCurrency): array
    {
        $accounts = Account::whereIn('accounts.team_id', $teamIds)
            ->byDetailTypes(AccountDetailType::ALL_CASH)
            ->with(['currencyBalances'])
            ->get();

        $groups = [];

        foreach ($accounts as $account) {
            $primaryBalance = (float) $account->balance;
            $primaryCurrency = $account->getPrimaryCurrency();

            $baseBalance = $primaryCurrency === $baseCurrency ? $primaryBalance : 0.0;
            $quoteBalance = $primaryCurrency === $quoteCurrency ? $primaryBalance : 0.0;

            if ($account->is_multi_currency) {
                foreach ($account->currencyBalances as $currencyBalance) {
                    if ($currencyBalance->currency_code === $baseCurrency) {
                        $baseBalance = (float) $currencyBalance->balance;
                    } elseif ($currencyBalance->currency_code === $quoteCurrency) {
                        $quoteBalance = (float) $currencyBalance->balance;
                    }
                }
            }

            $totalInQuote = $quoteBalance + ($baseBalance * $exchangeRate);

            $key = $account->bank_code ?: '__unlinked__';
            $label = $account->bank_code ?: 'Unlinked';

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'bank_code' => $account->bank_code,
                    'label' => $label,
                    'total_in_quote' => 0.0,
                    'account_count' => 0,
                ];
            }

            $groups[$key]['total_in_quote'] += $totalInQuote;
            $groups[$key]['account_count']++;
        }

        $list = array_values($groups);

        usort($list, fn ($a, $b) => $b['total_in_quote'] <=> $a['total_in_quote']);

        return $list;
    }

    private function buildAllGoals(array $teamIds): array
    {
        $goals = [];

        $funds = BudgetFund::whereIn('team_id', $teamIds)->get();
        foreach ($funds as $fund) {
            $goals[] = [
                'id' => "fund-{$fund->id}",
                'name' => $fund->name,
                'target_amount' => (float) $fund->amount,
                'current_balance' => 0.0,
            ];
        }

        $targets = BudgetTarget::whereIn('team_id', $teamIds)
            ->where('target_type', BudgetTarget::TYPE_SAVING_BALANCE)
            ->get();

        foreach ($targets as $target) {
            $goals[] = [
                'id' => "target-{$target->id}",
                'name' => $target->name,
                'target_amount' => (float) $target->amount,
                'current_balance' => 0.0,
            ];
        }

        return $goals;
    }

    /**
     * @param  array<int, array{id: int, name: string, team_id: int, team_name: string, available_in_quote: float}>  $categoryIndex
     */
    private function buildPinnedGoals(
        array $teamIds,
        array $pinnedIds,
        array $accounts = [],
        array $goalAccountLinks = [],
        array $goalCategoryLinks = [],
        array $categoryIndex = [],
    ): array {
        if (empty($pinnedIds)) {
            return [];
        }

        $accountIndex = [];
        foreach ($accounts as $group) {
            foreach ($group['accounts'] as $account) {
                $accountIndex[$account['id']] = [
                    'name' => $account['name'],
                    'bank_code' => $account['bank_code'] ?? null,
                    'balance_in_quote' => ($account['quote_balance'] ?? 0) + ($account['base_in_quote'] ?? 0),
                ];
            }
        }

        $categoryIndexById = [];
        foreach ($categoryIndex as $entry) {
            $categoryIndexById[$entry['id']] = $entry;
        }

        $all = $this->buildAllGoals($teamIds);
        $pinned = array_values(array_filter($all, fn ($goal) => in_array($goal['id'], $pinnedIds)));

        return array_map(function ($goal) use ($goalAccountLinks, $accountIndex, $goalCategoryLinks, $categoryIndexById) {
            $rawAccountIds = $goalAccountLinks[$goal['id']] ?? [];
            $accountIds = is_array($rawAccountIds) ? $rawAccountIds : [$rawAccountIds];

            $linkedAccounts = [];
            $total = 0.0;
            foreach ($accountIds as $accountId) {
                if (! isset($accountIndex[$accountId])) {
                    continue;
                }
                $linkedAccounts[] = [
                    'id' => (int) $accountId,
                    'name' => $accountIndex[$accountId]['name'],
                    'bank_code' => $accountIndex[$accountId]['bank_code'],
                    'balance_in_quote' => $accountIndex[$accountId]['balance_in_quote'],
                ];
                $total += $accountIndex[$accountId]['balance_in_quote'];
            }

            $rawCategoryIds = $goalCategoryLinks[$goal['id']] ?? [];
            $categoryIds = is_array($rawCategoryIds) ? $rawCategoryIds : [$rawCategoryIds];

            $linkedCategories = [];
            foreach ($categoryIds as $categoryId) {
                if (! isset($categoryIndexById[$categoryId])) {
                    continue;
                }
                $entry = $categoryIndexById[$categoryId];
                $linkedCategories[] = [
                    'id' => (int) $categoryId,
                    'name' => $entry['name'],
                    'team_name' => $entry['team_name'],
                    'available_in_quote' => $entry['available_in_quote'],
                ];
                $total += $entry['available_in_quote'];
            }

            $goal['linked_accounts'] = $linkedAccounts;
            $goal['linked_categories'] = $linkedCategories;
            $goal['current_balance'] = $total;

            return $goal;
        }, $pinned);
    }

    /**
     * @return array<int, array{id: int, name: string, team_id: int, team_name: string, available_in_quote: float}>
     */
    private function buildCategoryIndex(array $teamIds, float $exchangeRate, string $baseCurrency, string $quoteCurrency): array
    {
        $currentMonth = now()->startOfMonth()->format('Y-m-d');

        $teams = Team::query()->whereIn('id', $teamIds)->get()->keyBy('id');

        $budgetMonths = BudgetMonth::query()
            ->whereIn('budget_months.team_id', $teamIds)
            ->where('month', $currentMonth)
            ->join('categories', 'categories.id', '=', 'budget_months.category_id')
            ->whereNotNull('categories.parent_id')
            ->where('categories.resource_type', 'transactions')
            ->select([
                'budget_months.category_id',
                'budget_months.team_id',
                'budget_months.available',
                'categories.name',
            ])
            ->get();

        $index = [];
        foreach ($budgetMonths as $row) {
            $team = $teams->get($row->team_id);
            $teamName = $team?->name ?? '';

            $index[] = [
                'id' => (int) $row->category_id,
                'name' => $row->name,
                'team_id' => (int) $row->team_id,
                'team_name' => $teamName,
                'available_in_quote' => (float) $row->available,
            ];
        }

        return $index;
    }

    /**
     * @param  array<int, array{id: int, name: string, team_id: int, team_name: string, available_in_quote: float}>  $categoryIndex
     * @return array<int, array{team_name: string, categories: array<int, array{id: int, name: string, available_in_quote: float}>}>
     */
    private function groupCategoryIndexByTeam(array $categoryIndex): array
    {
        $grouped = [];

        foreach ($categoryIndex as $entry) {
            $teamName = $entry['team_name'];
            if (! isset($grouped[$teamName])) {
                $grouped[$teamName] = [
                    'team_name' => $teamName,
                    'categories' => [],
                ];
            }
            $grouped[$teamName]['categories'][] = [
                'id' => $entry['id'],
                'name' => $entry['name'],
                'team_id' => $entry['team_id'],
                'available_in_quote' => $entry['available_in_quote'],
            ];
        }

        return array_values($grouped);
    }

    private function calculateTotals(array $accounts, float $exchangeRate, string $baseCurrency, string $quoteCurrency): array
    {
        $totalBase = 0.0;
        $totalQuote = 0.0;

        foreach ($accounts as $group) {
            $totalBase += $group['subtotals']['base'];
            $totalQuote += $group['subtotals']['quote'];
        }

        $baseInQuote = $totalBase * $exchangeRate;
        $netWorth = $totalQuote + $baseInQuote;

        return [
            'total_base' => $totalBase,
            'total_quote' => $totalQuote,
            'base_in_quote' => $baseInQuote,
            'net_worth' => $netWorth,
            'base_currency' => $baseCurrency,
            'quote_currency' => $quoteCurrency,
        ];
    }
}
