<?php

namespace App\Mcp\Tools;

use App\Domains\Budget\Models\BudgetTarget;
use App\Mcp\Concerns\InteractsWithTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Budget targets')]
#[Name('list_budget_targets')]
#[Description('The household budget targets (monthly reminders like "Seguro de salud 15th", savings goals, loans) with their amount and payment day. payment_id is how a reminder shows in get_upcoming_payments; target_id is what update_budget_target expects.')]
#[IsReadOnly]
class ListBudgetTargetsTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request): Response
    {
        $targets = BudgetTarget::query()
            ->where('team_id', $this->teamId($request))
            ->with(['category' => fn ($query) => $query->without(['budget', 'matchAccount'])->select('id', 'name')])
            ->orderBy('frequency_month_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (BudgetTarget $target) => $target->category)
            ->map(fn (BudgetTarget $target) => self::present($target))
            ->values();

        return Response::json(['targets' => $targets]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(BudgetTarget $target): array
    {
        return [
            'target_id' => $target->id,
            'payment_id' => "budget_{$target->id}",
            'category_id' => $target->category_id,
            'category_name' => $target->category?->name,
            'target_type' => $target->target_type,
            'amount' => (float) $target->amount,
            'frequency' => $target->frequency,
            'frequency_month_date' => $target->frequency_month_date ? (int) $target->frequency_month_date : null,
            'notify' => (bool) $target->notify,
            'completed_at' => $target->completed_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
