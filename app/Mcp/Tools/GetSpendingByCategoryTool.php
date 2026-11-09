<?php

namespace App\Mcp\Tools;

use App\Domains\Today\Services\MeBriefService;
use App\Mcp\Concerns\InteractsWithTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Spending by category')]
#[Name('get_spending_by_category')]
#[Description('Verified spending per budget category for a month (default: current month), largest first. Answers "¿cuánto llevo en supermercado este mes?". Categories hidden from Insights are left out.')]
#[IsReadOnly]
class GetSpendingByCategoryTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request, MeBriefService $brief): Response
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        [$start, $end] = $this->monthRange($request, $validated['month'] ?? null);
        $categories = $brief->spendingByCategory($this->teamId($request), $start, $end);

        return Response::json([
            'month' => substr($start, 0, 7),
            'total' => round(array_sum(array_column($categories, 'total')), 2),
            'categories' => $categories,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'month' => $schema->string()->description('Month as YYYY-MM. Defaults to the current month in the household time zone.'),
        ];
    }
}
