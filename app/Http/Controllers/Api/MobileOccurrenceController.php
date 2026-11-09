<?php

namespace App\Http\Controllers\Api;

use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Housing\Services\OccurrenceService;
use App\Domains\Transaction\Actions\SearchTransactions;
use App\Http\Controllers\Controller;
use App\Http\Requests\OccurrencePreviewRequest;
use App\Http\Requests\OccurrenceRequest;
use App\Jobs\RunTeamChecks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Rhythms (occurrences) for the app, with the same options as the web: create,
 * edit, sync with transactions, preview a rule, remind, pause and delete.
 */
class MobileOccurrenceController extends Controller
{
    /**
     * Active rhythms with marks by default (what the app's carousel shows);
     * `?all=1` also returns paused and empty ones for the management screen.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(['occurrences' => $this->listFor((int) $request->user()->current_team_id, $request->boolean('all'))]);
    }

    public function store(OccurrenceRequest $request, OccurrenceService $service): JsonResponse
    {
        $occurrence = $service->create($request->user(), $request->validated());

        return response()->json(['occurrence' => $this->present($occurrence)], 201);
    }

    public function update(OccurrenceRequest $request, Occurrence $occurrence, OccurrenceService $service): JsonResponse
    {
        $this->ensureTeam($request, $occurrence);

        return response()->json(['occurrence' => $this->present($service->update($occurrence, $request->validated()))]);
    }

    public function destroy(Request $request, Occurrence $occurrence, OccurrenceService $service): JsonResponse
    {
        $this->ensureTeam($request, $occurrence);
        $service->delete($occurrence);

        return response()->json(['success' => true]);
    }

    public function sync(Request $request, Occurrence $occurrence, RegisterOccurrence $registerer): JsonResponse
    {
        $this->ensureTeam($request, $occurrence);
        $registerer->sync($occurrence);

        return response()->json(['occurrence' => $this->present($occurrence->refresh())]);
    }

    public function syncAll(Request $request): JsonResponse
    {
        $teamId = (int) $request->user()->current_team_id;
        RunTeamChecks::dispatchSync($teamId);

        return response()->json(['occurrences' => $this->listFor($teamId, true)]);
    }

    /**
     * Transactions a rule would match, without saving anything.
     */
    public function preview(OccurrencePreviewRequest $request, SearchTransactions $search): JsonResponse
    {
        $lines = $search->handle($request->validated('conditions'), (int) $request->user()->current_team_id) ?? collect();

        return response()->json([
            'count' => $lines->count(),
            'dates' => $lines->map(fn ($line) => Carbon::parse($line->date)->toDateString())->unique()->values(),
            'transactions' => $lines->map(fn ($line) => [
                'id' => $line->id,
                'transaction_id' => $line->transaction_id,
                'date' => Carbon::parse($line->date)->toDateString(),
                'concept' => $line->concept,
                'amount' => (float) (string) $line->amount,
                'category_id' => $line->category_id,
            ])->values(),
        ]);
    }

    public function remind(Request $request, Occurrence $occurrence, RegisterOccurrence $registerer): JsonResponse
    {
        $this->ensureTeam($request, $occurrence);
        $registerer->remind($occurrence);

        return response()->json(['reminded' => true]);
    }

    /**
     * Record "happened today". A rhythm with a rule is filled by its
     * transactions, so a manual mark is refused (it would vanish on sync).
     */
    public function addInstance(Request $request, Occurrence $occurrence, RegisterOccurrence $registerer): JsonResponse
    {
        $this->ensureTeam($request, $occurrence);
        if ($occurrence->hasRule()) {
            throw ValidationException::withMessages([
                'occurrence' => __('This rhythm is synced with its transactions. Use Sync instead of checking it by hand.'),
            ]);
        }

        $registerer->addTo($occurrence, Carbon::now()->format('Y-m-d'));

        return $this->instanceResponse($occurrence->refresh());
    }

    public function removeInstance(Request $request, Occurrence $occurrence, RegisterOccurrence $registerer): JsonResponse
    {
        $this->ensureTeam($request, $occurrence);
        $registerer->remove($occurrence->id);

        return $this->instanceResponse($occurrence->refresh());
    }

    private function instanceResponse(Occurrence $occurrence): JsonResponse
    {
        return response()->json([
            'success' => true,
            'last_date' => $occurrence->last_date?->format('Y-m-d'),
            'occurrence_count' => (int) $occurrence->occurrence_count,
            'occurrence' => $this->present($occurrence),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listFor(int $teamId, bool $all): array
    {
        return Occurrence::byTeam($teamId)
            ->when(! $all, fn ($query) => $query->where('is_active', true)->whereNotNull('last_date'))
            ->orderBy('name')
            ->get()
            ->map(fn (Occurrence $occurrence) => $this->present($occurrence))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Occurrence $occurrence): array
    {
        $daysSinceLast = $occurrence->last_date ? (int) $occurrence->last_date->diffInDays(now(), true) : null;
        $avg = (float) $occurrence->avg_days_passed;

        return [
            'id' => $occurrence->id,
            'name' => $occurrence->name,
            'type' => $occurrence->type,
            'last_date' => $occurrence->last_date?->format('Y-m-d'),
            'avg_days_passed' => round($avg, 1),
            'occurrence_count' => (int) $occurrence->occurrence_count,
            'days_since_last' => $daysSinceLast,
            'is_overdue' => $daysSinceLast !== null && $avg > 0 && $daysSinceLast >= $avg + Occurrence::DAYS_BEFORE,
            'days_until_next' => $occurrence->daysUntilNext(),
            'has_rule' => $occurrence->hasRule(),
            'conditions' => $occurrence->conditions ?: null,
            'is_active' => (bool) $occurrence->is_active,
            'notify_on_avg' => (bool) $occurrence->notify_on_avg,
            'notify_on_last_count' => (bool) $occurrence->notify_on_last_count,
            'previous_days_count' => (int) $occurrence->previous_days_count,
            'log' => array_values((array) ($occurrence->log ?? [])),
        ];
    }

    private function ensureTeam(Request $request, Occurrence $occurrence): void
    {
        abort_if((int) $occurrence->team_id !== (int) $request->user()->current_team_id, 403);
    }
}
