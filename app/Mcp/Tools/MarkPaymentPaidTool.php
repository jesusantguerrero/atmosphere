<?php

namespace App\Mcp\Tools;

use App\Domains\Transaction\Services\NextPaymentsService;
use App\Mcp\Concerns\InteractsWithTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Title('Mark payment paid')]
#[Name('mark_payment_paid')]
#[Description('Mark an upcoming payment from get_upcoming_payments as paid, recording its transaction. Only pending payments of this household can be marked; a payment already paid returns an error.')]
class MarkPaymentPaidTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request, NextPaymentsService $nextPayments): Response
    {
        if ($denied = $this->denyWithoutWriteAbility($request)) {
            return $denied;
        }

        $teamId = $this->teamId($request);
        $validated = $request->validate([
            'payment_id' => ['required', 'string', 'max:191'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('team_id', $teamId)],
        ]);

        $today = $this->today($request);
        $payment = $nextPayments->getNextPayments($teamId, $today->format('Y-m-d'))->firstWhere('id', $validated['payment_id']);
        if (! $payment) {
            return Response::error('That payment is not pending for this household. Call get_upcoming_payments for the current ids.');
        }

        $total = $validated['amount'] ?? $payment['total'];
        $paid = $nextPayments->markAsPaid($payment['id'], [
            'total' => $total,
            'date' => $validated['date'] ?? $today->format('Y-m-d'),
            'description' => $payment['title'],
            'account_id' => $validated['account_id'] ?? ($payment['account_id'] ?? null),
            'payee_id' => null,
            'team_id' => $teamId,
            'user_id' => $request->user()->id,
        ]);

        return $paid
            ? Response::json(['paid' => true, 'payment_id' => $payment['id'], 'title' => $payment['title'], 'total' => round((float) (string) $total, 2)])
            : Response::error('Loger could not mark this payment as paid.');
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'payment_id' => $schema->string()->description('The id from get_upcoming_payments, e.g. "budget_12".')->required(),
            'amount' => $schema->number()->description('Amount paid. Defaults to the payment total.'),
            'date' => $schema->string()->description('YYYY-MM-DD. Defaults to today.'),
            'account_id' => $schema->integer()->description('Account it was paid from.'),
        ];
    }
}
