<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithTeam;
use App\Models\Setting;
use App\Services\ShoppingListReviewService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Plan\Entities\FieldValue;
use Modules\Plan\Entities\Plan;

#[Title('Shopping trips')]
#[Name('list_shopping_trips')]
#[Description('The finished shopping trips of the household lists, newest first: the day, the list, the store, the total paid, what was bought and the transaction that paid for it once linked (transaction_id is what update_transaction expects). Use it to tell which supermarket charges were a list trip ("lo mío") and which were not.')]
#[IsReadOnly]
class ListShoppingTripsTool extends Tool
{
    use InteractsWithTeam;

    /** Trips are read newest first and this far back at most; a month of daily trips fits many times over. */
    private const MAX_TRIPS = 500;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $teamId = $this->teamId($request);
        [$start, $end] = ($validated['start_date'] ?? $validated['end_date'] ?? null)
            ? [$validated['start_date'] ?? '0000-01-01', $validated['end_date'] ?? '9999-12-31']
            : $this->monthRange($request, $validated['month'] ?? null);
        $timezone = Setting::getByTeam($teamId)['team_timezone'] ?? 'America/Santo_Domingo';

        $rows = FieldValue::query()
            ->where(['team_id' => $teamId, 'entity_type' => Plan::class, 'resource' => 'plan'])
            ->where('field_name', 'like', 'shopping_visit_%')
            ->latest('id')
            ->limit(self::MAX_TRIPS)
            ->get(['entity_id', 'value'])
            ->map(fn (FieldValue $row) => ['plan_id' => (int) $row->entity_id, 'visit' => json_decode($row->value, true) ?: []])
            ->filter(fn (array $row) => ! empty($row['visit']['id']) && ! empty($row['visit']['completed_at']))
            ->map(fn (array $row) => [...$row, 'date' => Carbon::parse($row['visit']['completed_at'])->setTimezone($timezone)->format('Y-m-d')])
            ->filter(fn (array $row) => $row['date'] >= $start && $row['date'] <= $end)
            ->sortByDesc(fn (array $row) => $row['visit']['completed_at'])
            ->values();

        $lists = Plan::query()->where('team_id', $teamId)->whereIn('id', $rows->pluck('plan_id')->unique()->all())->pluck('name', 'id');
        $visits = app(ShoppingListReviewService::class)->decorate($teamId, $rows->pluck('visit')->all());

        $trips = $rows->values()->map(fn (array $row, int $index) => [
            'date' => $row['date'],
            'list' => $lists->get($row['plan_id']),
            'store' => $visits[$index]['payee']['name'] ?? null,
            'total' => round((float) ($row['visit']['total'] ?? 0), 2),
            'currency_code' => $row['visit']['currency'] ?? null,
            'note' => ($row['visit']['note'] ?? '') ?: null,
            'items' => collect($row['visit']['purchased_items'] ?? [])->pluck('title')->filter()->values()->all(),
            'transaction_id' => $visits[$index]['transaction']['id'] ?? null,
            'transaction_date' => isset($visits[$index]['transaction']) ? substr($visits[$index]['transaction']['date'], 0, 10) : null,
        ]);

        return Response::json(['from' => $start, 'to' => $end, 'total' => round($trips->sum('total'), 2), 'trips' => $trips]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'month' => $schema->string()->description('Month as YYYY-MM. Defaults to the current month.'),
            'start_date' => $schema->string()->description('From this date, YYYY-MM-DD, instead of a month.'),
            'end_date' => $schema->string()->description('Up to this date, YYYY-MM-DD, instead of a month.'),
        ];
    }
}
