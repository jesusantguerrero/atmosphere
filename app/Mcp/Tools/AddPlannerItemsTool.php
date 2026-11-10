<?php

namespace App\Mcp\Tools;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Today\Services\BulkPlannerService;
use App\Http\Requests\BulkPlannerStoreRequest;
use App\Mcp\Concerns\InteractsWithTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Title('Add planner items')]
#[Name('add_planner_items')]
#[Description('Add dated items to the household Planner in one go ("importa este calendario del colegio"). Read the document (school calendar, circular, PDF) and extract one item per dated event: its date, a short name, the amount to pay if any, and useful notes. Show the list to the user and confirm it before calling. Up to 100 items per call.')]
class AddPlannerItemsTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request, BulkPlannerService $bulk): Response
    {
        if ($denied = $this->denyWithoutWriteAbility($request)) {
            return $denied;
        }

        $rules = new BulkPlannerStoreRequest;
        $validated = $request->validate([
            ...$rules->rules(),
            'items.*.date' => 'required|date_format:Y-m-d',
            'items.*.end_date' => 'nullable|date_format:Y-m-d|after_or_equal:items.*.date',
        ], $rules->messages());

        $user = $request->user();
        $planners = $bulk->createMany(
            teamId: $this->teamId($request),
            userId: $user->id,
            items: $validated['items'],
            source: $validated['source'] ?? null,
        );

        return Response::json([
            'created' => $planners->count(),
            'items' => $planners->map(fn (Planner $planner) => [
                'id' => $planner->id,
                'date' => $planner->date?->format('Y-m-d'),
                'end_date' => $planner->end_date?->format('Y-m-d'),
                'name' => $planner->name,
                'total' => $planner->total === null ? null : round((float) $planner->total, 2),
                'notes' => $planner->notes,
            ])->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'source' => $schema->string()->description('Where the items come from, e.g. "school". Shown as their source in the Planner.'),
            'items' => $schema->array()->items($schema->object([
                'date' => $schema->string()->description('YYYY-MM-DD.')->required(),
                'end_date' => $schema->string()->description('YYYY-MM-DD, for events spanning several days. Defaults to date.'),
                'name' => $schema->string()->description('Short name, e.g. "Excursión al planetario".')->required(),
                'total' => $schema->number()->description('Amount to pay, if the event costs money.'),
                'notes' => $schema->string()->description('What to bring or prepare.'),
            ]))->description('One item per dated event.')->required(),
        ];
    }
}
