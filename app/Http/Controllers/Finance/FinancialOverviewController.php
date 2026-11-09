<?php

namespace App\Http\Controllers\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Services\FinancialOverviewService;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class FinancialOverviewController extends Controller
{
    const DEFAULT_EXCHANGE_RATE = FinancialOverviewService::DEFAULT_EXCHANGE_RATE;

    const SETTING_EXCHANGE_RATE = FinancialOverviewService::SETTING_EXCHANGE_RATE;

    const SETTING_BASE_CURRENCY = FinancialOverviewService::SETTING_BASE_CURRENCY;

    const SETTING_QUOTE_CURRENCY = FinancialOverviewService::SETTING_QUOTE_CURRENCY;

    const SETTING_PINNED_GOALS = FinancialOverviewService::SETTING_PINNED_GOALS;

    const SETTING_GOAL_ACCOUNT_LINKS = FinancialOverviewService::SETTING_GOAL_ACCOUNT_LINKS;

    const SETTING_GOAL_CATEGORY_LINKS = FinancialOverviewService::SETTING_GOAL_CATEGORY_LINKS;

    public function index(Request $request, FinancialOverviewService $overview): Response
    {
        return inertia('Trends/FinancialOverview', $overview->build($request->user()));
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
}
