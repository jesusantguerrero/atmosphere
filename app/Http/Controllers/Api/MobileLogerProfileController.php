<?php

namespace App\Http\Controllers\Api;

use App\Domains\LogerProfile\Models\LogerProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\StoreLogerProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Household profiles (people, pets, the house) for the app. A guest only gets the
 * profiles she was granted.
 */
class MobileLogerProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $team = $user->currentTeam;

        $profiles = LogerProfile::query()->where('team_id', $team->id)
            ->when($user->isGuestOf($team), fn ($query) => $query->whereIn('id', $user->grantedProfileIds($team)))
            ->orderBy('name')->orderBy('id')->get();

        return response()->json(['profiles' => $profiles->map(fn (LogerProfile $profile) => $this->present($profile))->values()]);
    }

    public function store(StoreLogerProfileRequest $request): JsonResponse
    {
        $profile = LogerProfile::create([
            ...$request->validated(),
            'team_id' => $request->user()->current_team_id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['profile' => $this->present($profile)], 201);
    }

    /**
     * @return array{id: int, name: string, color: string}
     */
    private function present(LogerProfile $profile): array
    {
        return ['id' => $profile->id, 'name' => $profile->name, 'color' => $profile->color];
    }
}
