<?php

namespace App\Domains\Housing\Http\Controllers;

use App\Domains\Housing\Services\ChoreAreaService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChoreAreaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Plan\Entities\PlanItem;

/**
 * JSON for the «Áreas» view of the household chores page.
 */
class ChoreAreaController extends Controller
{
    public function index(Request $request, ChoreAreaService $choreAreas): JsonResponse
    {
        return response()->json($choreAreas->overview((int) $request->user()->current_team_id));
    }

    /**
     * Saved without model events: the Plan module's updating hook clears
     * commit_date on pending chores, which would wipe a set due date.
     */
    public function update(UpdateChoreAreaRequest $request, PlanItem $item, ChoreAreaService $choreAreas): JsonResponse
    {
        PlanItem::withoutEvents(fn () => $item->forceFill(['area' => $request->validated('area')])->save());

        return response()->json($choreAreas->describe($item->fresh()));
    }
}
