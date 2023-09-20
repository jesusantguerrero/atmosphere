<?php

namespace App\Domains\LogerProfile\Http\Controllers;

use App\Domains\LogerProfile\Data\ProfileEntityData;
use App\Domains\LogerProfile\Services\LogerProfileService;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasEnrichedRequest;

class LogerProfileEntityController extends Controller
{
    use HasEnrichedRequest;

    public function index(LogerProfileService $profileService)
    {
        return inertia('LogerProfile/Index', [
            'profiles' => $profileService->list(auth()->user()->current_team_id),
        ]);
    }

    public function store(int $profileId, LogerProfileService $profileService)
    {
        $profileService->addProfileEntity(
            ProfileEntityData::forVue(
                array_merge($this->getPostData(), [
                    'profile_id' => $profileId,
                ])
            )
        );
    }

    public function show(LogerProfileService $profileService, int $profileId)
    {

        return inertia('LogerProfile/ProfileView', [
            'profiles' => $profileService->list(auth()->user()->current_team_id),
            'profile' => $profileService->getById($profileId),
            'entities' => function () use ($profileId, $profileService) {
                return $profileService->getEntitiesByProfileId($profileId) ?? [];
            },
        ]);
    }
}
