<?php

namespace App\Http\Controllers\System;

use App\Domains\Today\Services\BulkPlannerService;
use App\Http\Requests\BulkPlannerStoreRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Bulk creation of free-form Planner rows (school activities, family TODOs,
 * errands). Reuses the existing Planner table; surfaces in /today via
 * TodayService::today().
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
}
