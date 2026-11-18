<?php

namespace App\Mcp\Tools;

use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Services\BudgetTargetService;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Mcp\Concerns\InteractsWithTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Title('Update budget target')]
#[Name('update_budget_target')]
#[Description('Change a budget target of the household: its amount (the bill went up from 5,241.60 to 6,342.34), its payment day or whether it reminds. Find target_id with list_budget_targets, or pass the payment_id (budget_4) from get_upcoming_payments. Before calling, show the user the current and the new values and wait for their confirmation. Returns the target and this_month: paid when a verified transaction already settles the new amount, pending when the reminder still shows, not_reminded when it has no reminder.')]
class UpdateBudgetTargetTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request, BudgetTargetService $service, NextPaymentsService $nextPayments): Response
    {
        if ($denied = $this->denyWithoutWriteAbility($request)) {
            return $denied;
        }

        $validated = $request->validate([
            'target_id' => ['required_without:payment_id', 'prohibits:payment_id', 'integer'],
            'payment_id' => ['required_without:target_id', 'string', 'regex:/^budget_\d+$/'],
            'amount' => ['sometimes', 'numeric', 'gt:0', 'max:999999999.99'],
            'frequency_month_date' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'notify' => ['sometimes', 'boolean'],
        ], [
            'target_id.prohibits' => 'Send target_id or payment_id, not both.',
            'payment_id.regex' => 'payment_id must look like budget_4 (a budget reminder from get_upcoming_payments).',
        ]);

        $teamId = $this->teamId($request);
        $targetId = (int) ($validated['target_id'] ?? substr($validated['payment_id'], strlen('budget_')));
        $target = BudgetTarget::query()->where('team_id', $teamId)->with('category')->find($targetId);
        if (! $target || ! $target->category) {
            return Response::error('That budget target is not in this household. Call list_budget_targets for the ids.');
        }

        $changes = collect($validated)->only(['amount', 'frequency_month_date', 'notify']);
        if ($changes->isEmpty()) {
            return Response::error('Nothing to change: send amount, frequency_month_date or notify.');
        }
        if ($changes->has('amount') && $target->target_type === BudgetTarget::TYPE_LOAN) {
            return Response::error('A loan target computes its amount from the loan; change the loan in Loger instead.');
        }
        if ($request->user()->cannot('update', $target->category)) {
            return Response::error('You cannot change the budget of this household.');
        }

        $service->update($target->category, $target, $request->user(), $changes->all());
        $target->refresh();

        return Response::json([
            ...ListBudgetTargetsTool::present($target),
            'this_month' => $this->thisMonth($target, $nextPayments, $teamId, $request),
        ]);
    }

    private function thisMonth(BudgetTarget $target, NextPaymentsService $nextPayments, int $teamId, Request $request): string
    {
        if (! $target->notify || ! $target->frequency_month_date || $target->completed_at) {
            return 'not_reminded';
        }

        $listed = $nextPayments->getNextPayments($teamId, $this->today($request)->format('Y-m-d'))
            ->contains('id', "budget_{$target->id}");

        return $listed ? 'pending' : 'paid';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'target_id' => $schema->integer()->description('Target id from list_budget_targets.'),
            'payment_id' => $schema->string()->description('Instead of target_id: the reminder id from get_upcoming_payments, e.g. budget_4.'),
            'amount' => $schema->number()->description('New positive monthly amount.'),
            'frequency_month_date' => $schema->integer()->min(1)->max(31)->description('Day of the month it is due.'),
            'notify' => $schema->boolean()->description('Whether it shows as a reminder.'),
        ];
    }
}
