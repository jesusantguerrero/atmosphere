<?php

namespace App\Mcp\Tools;

use App\Domains\Transaction\Services\NextPaymentsService;
use App\Mcp\Concerns\InteractsWithTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Upcoming payments')]
#[Name('get_upcoming_payments')]
#[Description('Bills and payments due soon: budget reminders, credit card payments and planned transactions, overdue ones included. Answers "¿qué pagos vienen esta semana?". The returned id is what mark_payment_paid expects.')]
#[IsReadOnly]
class GetUpcomingPaymentsTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request, NextPaymentsService $nextPayments): Response
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);
        $today = $this->today($request);
        $until = $today->copy()->addDays((int) ($validated['days'] ?? 7))->format('Y-m-d');

        $payments = $nextPayments->getNextPayments($this->teamId($request), $today->format('Y-m-d'))
            ->filter(fn (array $payment) => ($payment['due_date'] ?? null) && $payment['due_date'] <= $until)
            ->map(fn (array $payment) => [
                'id' => $payment['id'],
                'type' => $payment['type'],
                'title' => $payment['title'],
                'total' => round((float) (string) $payment['total'], 2),
                'due_date' => $payment['due_date'],
                'overdue' => $payment['due_date'] < $today->format('Y-m-d'),
                'category_id' => $payment['category_id'] ?? null,
                'account_id' => $payment['account_id'] ?? null,
            ])
            ->values();

        return Response::json([
            'until' => $until,
            'total' => round($payments->sum('total'), 2),
            'payments' => $payments,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->min(1)->max(90)->description('How many days ahead to look (default 7).'),
        ];
    }
}
