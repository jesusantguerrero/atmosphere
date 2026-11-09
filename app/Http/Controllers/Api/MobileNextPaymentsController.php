<?php

namespace App\Http\Controllers\Api;

use App\Domains\Transaction\Models\NextPaymentDismissal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\DismissNextPaymentRequest;
use Illuminate\Http\JsonResponse;

/**
 * Token twin of NextPaymentsController::dismiss/undismiss. Dismissals are scoped
 * to the current team, so an id from another household never hides its payments.
 */
class MobileNextPaymentsController extends Controller
{
    public function dismiss(DismissNextPaymentRequest $request): JsonResponse
    {
        NextPaymentDismissal::firstOrCreate(
            [
                'team_id' => $request->user()->current_team_id,
                'payment_key' => $request->validated('payment_id'),
                'period' => $request->validated('period'),
            ],
            ['user_id' => $request->user()->id]
        );

        return response()->json(['dismissed' => true]);
    }

    public function undismiss(DismissNextPaymentRequest $request): JsonResponse
    {
        NextPaymentDismissal::query()
            ->where('team_id', $request->user()->current_team_id)
            ->where('payment_key', $request->validated('payment_id'))
            ->where('period', $request->validated('period'))
            ->delete();

        return response()->json(['dismissed' => false]);
    }
}
