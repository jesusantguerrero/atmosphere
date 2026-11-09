<?php

namespace App\Http\Controllers\Api;

use App\Domains\Transaction\Services\CreditCardReportService;
use App\Domains\Transaction\Services\SpendingByFundingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\TrendsPeriodRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * The web Trends sections for the app: credit cards (with the journey) and,
 * new here, how spending was funded (card vs cash/debit).
 */
class MobileTrendsController extends Controller
{
    public function creditCards(TrendsPeriodRequest $request, CreditCardReportService $cards, SpendingByFundingService $funding): JsonResponse
    {
        $teamId = (int) $request->user()->current_team_id;
        $today = Carbon::now(Setting::getByTeam($teamId)['team_timezone'] ?? 'America/Santo_Domingo');
        $start = $request->validated('start') ?? $today->copy()->startOfMonth()->format('Y-m-d');
        $end = $request->validated('end') ?? $today->copy()->endOfMonth()->format('Y-m-d');
        $accountIds = $request->validated('account_ids') ? array_map('intval', $request->validated('account_ids')) : null;

        $report = $cards->creditCards($teamId, $end, $start, $accountIds);
        $billing = $report['billingCyclesByCard'] ?? [];

        return response()->json([
            'period' => ['start' => $start, 'end' => $end],
            ...self::presentCreditCards($report),
            'billing_cycles' => [
                'cards' => collect($billing['data'] ?? [])->map(fn ($row) => [
                    'id' => $row->id,
                    'name' => $row->name,
                    'subtotal' => (float) (string) $row->subtotal,
                    'total' => (float) (string) $row->total,
                    'discounts' => (float) (string) $row->discounts,
                ])->values(),
                'total' => (float) (string) ($billing['total'] ?? 0),
                'subtotal' => (float) (string) ($billing['subtotal'] ?? 0),
                'discount_total' => (float) (string) ($billing['discountTotal'] ?? 0),
            ],
            'journey' => $report['journey'] ?? null,
            'categories_by_funding' => $funding->categoriesByFunding($teamId, $start, $end),
            'spent_by_kind' => $funding->spentByKind($teamId, $start, $end),
        ]);
    }

    /**
     * Mobile shape of CreditCardReportService::creditCards(), shared with /insights.
     *
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    public static function presentCreditCards(array $report): array
    {
        return [
            'has_credit_cards' => (bool) ($report['hasCreditCards'] ?? false),
            'credit_total' => (float) (string) ($report['creditTotal'] ?? 0),
            'credit_total_previous' => (float) (string) ($report['creditTotalPrevious'] ?? 0),
            'credit_total_delta' => (float) (string) ($report['creditTotalDelta'] ?? 0),
            'credit_capacity' => (float) (string) ($report['creditCapacity'] ?? 0),
            'credit_line_usage' => (float) ($report['creditLineUsage'] ?? 0),
            'cards' => collect($report['lastCycleBalances'] ?? [])->map(fn ($card) => [
                'id' => $card->id,
                'name' => $card->name,
                'credit_limit' => (float) (string) ($card->credit_limit ?? 0),
                'cycle_total' => (float) (string) ($card->total ?? 0),
                'cycle_from' => $card->from ?? null,
                'cycle_until' => $card->until ?? null,
            ])->values(),
            'top_categories' => collect($report['topCategoriesByCard'] ?? [])->map(fn ($row) => [
                'card_name' => $row->name,
                'category' => $row->cat_name,
                'total' => (float) (string) ($row->total ?? 0),
            ])->values(),
            'top_payees' => collect($report['topPayeesByCard'] ?? [])->map(fn ($row) => [
                'card_name' => $row->name ?? null,
                'payee' => $row->cat_name ?? null,
                'total' => (float) (string) ($row->total ?? 0),
            ])->values(),
        ];
    }
}
