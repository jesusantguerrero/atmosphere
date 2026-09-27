<?php

namespace App\Http\Controllers;

use App\Domains\Transaction\Services\NextPaymentsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NextPaymentsController extends Controller
{
    public function __construct(private NextPaymentsService $nextPaymentsService) {}

    public function index(Request $request)
    {
        $teamId = $request->user()->current_team_id;
        $date = $request->get('date');

        $nextPayments = $this->nextPaymentsService->getNextPayments($teamId, $date);

        return response()->json([
            'data' => $nextPayments,
            'summary' => [
                'total_amount' => $nextPayments->sum('amount'),
                'total_count' => $nextPayments->count(),
                'by_type' => $nextPayments->groupBy('type')->map->count(),
            ],
        ]);
    }

    public function markAsPaid(Request $request, string $paymentId)
    {
        $teamId = $request->user()->current_team_id;
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('team_id', $teamId)],
            'payee_id' => ['nullable', Rule::exists('payees', 'id')->where('team_id', $teamId)],
        ]);

        $success = $this->nextPaymentsService->markAsPaid($paymentId, [
            'total' => $request->amount,
            'date' => $request->date,
            'description' => $request->description,
            'account_id' => $request->account_id,
            'payee_id' => $request->payee_id,
            'team_id' => $request->user()->current_team_id,
            'user_id' => $request->user()->id,
        ]);

        if ($success) {
            return response()->json(['message' => 'Payment marked as paid successfully']);
        }

        return response()->json(['message' => 'Failed to mark payment as paid'], 400);
    }

    /**
     * Full-page, filterable view of the same unified "next payments" the
     * dashboard hero counts (budget reminders + credit-card cuts + planned
     * transactions). Because that list is a synthetic mix of three sources,
     * no generic /finance/transactions filter can reproduce it — so the hero's
     * "overdue payments" / "due soon" cards deep-link here with ?filter=... and
     * we narrow the SAME service output with the SAME day predicate the hero
     * uses (date strictly before today = overdue; today..+7 = due soon). This
     * guarantees the page shows exactly the items the card counted.
     */
    public function page(Request $request)
    {
        $teamId = $request->user()->current_team_id;
        $filter = $request->get('filter', 'all');

        $today = now()->startOfDay();
        $payments = $this->nextPaymentsService->getNextPayments($teamId)
            ->filter(function ($payment) use ($filter, $today) {
                $raw = $payment['date'] ?? $payment['due_date'] ?? null;
                if (! $raw) {
                    // Undated items only belong in the unfiltered "all" view.
                    return $filter === 'all';
                }
                $date = Carbon::parse($raw)->startOfDay();

                return match ($filter) {
                    'overdue' => $date->lt($today),
                    'due_soon' => $date->gte($today) && $date->lte($today->copy()->addDays(7)),
                    default => true,
                };
            })
            ->values();

        return inertia('Finance/NextPayments', [
            'sectionTitle' => 'Next Payments',
            'payments' => $payments,
            'filter' => in_array($filter, ['overdue', 'due_soon'], true) ? $filter : 'all',
        ]);
    }
}
