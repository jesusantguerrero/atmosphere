<?php

namespace App\Domains\LogerProfile\Http\Controllers;

use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileWorkspaceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Modules\Plan\Entities\Plan;

class ProfileWorkspaceController extends Controller
{
    public function __construct(private ProfileWorkspaceService $workspace) {}

    private function profile(Request $request, int $profileId): LogerProfile
    {
        return LogerProfile::query()->where('team_id', $request->user()->current_team_id)->findOrFail($profileId);
    }

    public function show(Request $request, int $profileId): Response
    {
        $profile = $this->profile($request, $profileId);

        return inertia('LogerProfile/WorkspacePreview', ['profile' => $profile->only(['id', 'name']), 'workspace' => $this->workspace->snapshot($profile)]);
    }

    private function result(LogerProfile $profile, array $extra = []): JsonResponse
    {
        return response()->json(['workspace' => $this->workspace->snapshot($profile), ...$extra]);
    }

    public function createBoard(ProfileWorkspaceRequest $request, int $profileId): JsonResponse
    {
        $profile = $this->profile($request, $profileId);
        $board = $this->workspace->createBoard($profile, $request->validated(), $request->user()->id);

        return $this->result($profile, ['board_id' => (string) $board->id]);
    }

    public function updateBoard(ProfileWorkspaceRequest $request, int $profileId, int $boardId): JsonResponse
    {
        $profile = $this->profile($request, $profileId);
        $this->workspace->linked($profile, 'plan', $boardId)->forceFill(['last_view_id' => ProfileWorkspaceService::VIEWS[$request->validated('view')]])->save();

        return $this->result($profile);
    }

    public function createNotebook(ProfileWorkspaceRequest $request, int $profileId, int $boardId): JsonResponse
    {
        $profile = $this->profile($request, $profileId);
        $board = $this->workspace->linked($profile, 'plan', $boardId);
        $notebook = $this->workspace->createStage($profile, $board, $request->validated('name'), $request->user()->id);

        return $this->result($profile, ['notebook_id' => (string) $notebook->id]);
    }

    public function createPage(ProfileWorkspaceRequest $request, int $profileId, int $boardId): JsonResponse
    {
        $profile = $this->profile($request, $profileId);
        $board = $this->workspace->linked($profile, 'plan', $boardId);
        $stage = $board->stages()->findOrFail($request->validated('stage_id'));
        $item = $this->workspace->createPage($profile, $board, $stage, $request->validated(), $request->user()->id);

        return $this->result($profile, ['item_id' => (string) $item->id]);
    }

    public function updateItem(ProfileWorkspaceRequest $request, int $profileId, int $itemId): JsonResponse
    {
        $profile = $this->profile($request, $profileId);
        $item = $this->workspace->item($profile, $itemId);
        $data = $request->validated();
        $item->getConnection()->transaction(function () use ($item, $data, $profile) {
            if (array_key_exists('title', $data)) {
                $item->title = $data['title'];
            }
            if (array_key_exists('done', $data)) {
                $item->is_done = $data['done'];
            }
            $item->save();
            $board = Plan::query()->where('team_id', $profile->team_id)->findOrFail($item->plan_id);
            $this->workspace->saveItemFields($board, $item, $data);
        });

        return $this->result($profile);
    }

    public function saveLinks(ProfileWorkspaceRequest $request, int $profileId): JsonResponse
    {
        $profile = $this->profile($request, $profileId);
        $this->workspace->saveLinks($profile, $request->validated('selections'));

        return $this->result($profile);
    }

    public function registerOccurrence(ProfileWorkspaceRequest $request, int $profileId, int $occurrenceId): JsonResponse
    {
        $profile = $this->profile($request, $profileId);
        $this->workspace->registerToday($profile, $occurrenceId, $request->boolean('checked'));

        return $this->result($profile);
    }
}
