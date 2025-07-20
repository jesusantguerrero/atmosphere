<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Domains\Transaction\Services\NextPaymentsService;

class NextPaymentsController extends Controller
{
    public function __construct(private NextPaymentsService $nextPaymentsService)
    {
    }

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
            ]
        ]);
    }

    public function markAsPaid(Request $request, string $paymentId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'account_id' => 'nullable|exists:accounts,id',
            'payee_id' => 'nullable|exists:payees,id',
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
}