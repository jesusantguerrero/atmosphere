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

#[Title('Month brief')]
#[Name('get_month_brief')]
#[Description('How the household is doing this month: spent so far vs the same day last month and the 3-month average, projected total, income, categories that deviate, fixed charges that went up, savings rate, overspent budget lines and occurrences speeding up. Use it for "¿cómo voy este mes?" questions.')]
#[IsReadOnly]
class GetMonthBriefTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request, MeBriefService $brief): Response
    {
        $options = $request->validate([
            'min_delta' => ['nullable', 'numeric', 'min:0'],
            'min_pct' => ['nullable', 'numeric', 'min:0'],
        ]);

        $summary = $brief->build($this->teamId($request), $options);
        unset($summary['month']['daily_cumulative']);

        return Response::json($summary);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'min_delta' => $schema->number()->description('Minimum difference vs the 3-month average for a category to be listed (default 1500, team currency). Use 0 to list all.'),
            'min_pct' => $schema->number()->description('Minimum percentage difference for a category to be listed (default 15). Use 0 to list all.'),
        ];
    }
}
