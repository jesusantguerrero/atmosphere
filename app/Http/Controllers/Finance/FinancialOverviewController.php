<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Budget\Models\BudgetFund;
use App\Domains\Budget\Models\BudgetTarget;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Setting;
use Insane\Journal\Models\Core\AccountDetailType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class FinancialOverviewController extends Controller
{
    const DEFAULT_EXCHANGE_RATE = 59.8;

    const SETTING_EXCHANGE_RATE = 'financial_overview_exchange_rate';

    const SETTING_BASE_CURRENCY = 'financial_overview_base_currency';

    const SETTING_QUOTE_CURRENCY = 'financial_overview_quote_currency';

    const SETTING_PINNED_GOALS = 'financial_overview_pinned_goals';

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

        $accounts = $this->buildAccountsData($teamIds, $exchangeRate, $baseCurrency, $quoteCurrency);
        $pinnedGoals = $this->buildPinnedGoals($teamIds, $pinnedGoalIds);
        $availableGoals = $this->buildAllGoals($teamIds);
        $totals = $this->calculateTotals($accounts, $exchangeRate, $baseCurrency, $quoteCurrency);

        return inertia('Trends/FinancialOverview', [
            'accounts' => $accounts,
            'pinnedGoals' => $pinnedGoals,
            'availableGoals' => $availableGoals,
            'pinnedGoalIds' => $pinnedGoalIds,
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

    private function buildPinnedGoals(array $teamIds, array $pinnedIds): array
    {
        if (empty($pinnedIds)) {
            return [];
        }

        $all = $this->buildAllGoals($teamIds);

        return array_values(array_filter($all, fn ($goal) => in_array($goal['id'], $pinnedIds)));
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
