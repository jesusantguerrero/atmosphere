<?php

namespace App\Http\Controllers\System;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Today\Services\BulkPlannerService;
use App\Http\Requests\BulkPlannerStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Planner CRUD — free-form dated items (school activities, family TODOs,
 * errands, planned expenses). Bulk creation + single-item CRUD for Calendar.
 */
class PlannerController
{
    public function __construct(private BulkPlannerService $bulk) {}

    public function bulkStore(BulkPlannerStoreRequest $request): RedirectResponse
    {
        $user = $request->user();

        $this->bulk->createMany(
            teamId: $user->current_team_id,
            userId: $user->id,
            items: $request->validated('items'),
            source: $request->validated('source'),
        );

        return back();
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        Planner::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => $request->name,
            'date' => $request->date,
            'end_date' => $request->end_date,
            'notes' => $request->notes,
            'total' => $request->total,
            'source' => $request->source,
            'status' => Planner::STATUS_PENDING,
        ]);

        return back();
    }

    public function update(Request $request, Planner $planner): RedirectResponse
    {
        abort_if($planner->team_id !== $request->user()->current_team_id, 403);

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:planned,pending,completed,cancelled'],
        ]);

        $planner->update($request->only(['name', 'date', 'end_date', 'notes', 'total', 'status']));

        return back();
    }

    public function destroy(Request $request, Planner $planner): RedirectResponse
    {
        abort_if($planner->team_id !== $request->user()->current_team_id, 403);

        $planner->delete();

        return back();
    }

    public function complete(Request $request, Planner $planner): RedirectResponse
    {
        abort_if($planner->team_id !== $request->user()->current_team_id, 403);

        $planner->markAsCompleted(null, $request->input('notes'));

        return back();
    }

    /**
     * Stop sending reminders for this planner without asserting it was paid.
     * Use when the user wants to dismiss a recurring notification (e.g. paid
     * via untracked cash) but doesn't want to log a transaction.
     */
    public function cancel(Request $request, Planner $planner): RedirectResponse
    {
        abort_if($planner->team_id !== $request->user()->current_team_id, 403);

        $planner->markAsCancelled($request->input('notes'));

        return back();
    }
}
