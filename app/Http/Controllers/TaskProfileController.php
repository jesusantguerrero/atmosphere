<?php

namespace App\Http\Controllers;

use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Models\LogerProfileEntity;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use App\Http\Requests\TaskProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;

class TaskProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()->current_team_id;
        $plan = Plan::query()->where('team_id', $teamId)->findOrFail($request->integer('plan_id'));
        $itemIds = PlanItem::query()->where('team_id', $teamId)->where('plan_id', $plan->id)->pluck('id');
        $links = LogerProfileEntity::query()->without('entity')->where('team_id', $teamId)->where('entity_type', PlanItem::class)->whereIn('entity_id', $itemIds)->get();
        $profiles = LogerProfile::query()->where('team_id', $teamId)->whereIn('id', $links->pluck('profile_id'))->get(['id', 'name'])->keyBy('id');

        return response()->json(['links' => $links->groupBy('entity_id')->map(fn ($items) => $items->map(fn ($link) => $profiles->get($link->profile_id))->filter()->values())]);
    }

    private function task(Request $request, int $itemId): PlanItem
    {
        return PlanItem::query()->where('team_id', $request->user()->current_team_id)->findOrFail($itemId);
    }

    public function show(Request $request, int $itemId): JsonResponse
    {
        $task = $this->task($request, $itemId);

        return response()->json(['profiles' => LogerProfile::query()->where('team_id', $task->team_id)->orderBy('name')->get(['id', 'name']), 'profile_ids' => LogerProfileEntity::query()->without('entity')->where('team_id', $task->team_id)->where('entity_type', PlanItem::class)->where('entity_id', $task->id)->pluck('profile_id')]);
    }

    public function update(TaskProfileRequest $request, int $itemId, ProfileWorkspaceService $workspace): JsonResponse
    {
        $task = $this->task($request, $itemId);
        $workspace->setTaskProfiles($task, $request->validated('profile_ids'));

        return $this->show($request, $itemId);
    }
}
