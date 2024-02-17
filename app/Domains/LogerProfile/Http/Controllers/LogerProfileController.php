<?php

namespace App\Domains\LogerProfile\Http\Controllers;

use App\Models\Setting;
use App\Http\Controllers\Controller;
use App\Domains\LogerProfile\Data\LogerProfileData;
use App\Http\Controllers\Traits\HasEnrichedRequest;
use App\Domains\LogerProfile\Services\LogerProfileService;

class LogerProfileController extends Controller
{
    use HasEnrichedRequest;

    public function index(LogerProfileService $profileService)
    {
        return inertia('LogerProfile/Index', [
            'profiles' => $profileService->list(auth()->user()->current_team_id),
        ]);
    }

    public function store(LogerProfileService $profileService)
    {

        $profileService->create(
            LogerProfileData::from($this->getPostData())
        );
    }

    public function show(LogerProfileService $profileService, int $profileId)
    {
        return inertia('LogerProfile/ProfileView', [
            'profiles' => $profileService->list(auth()->user()->current_team_id),
            'profile' => $profileService->getById($profileId),
            'entities' => function () use ($profileId, $profileService) {
                return $profileService->getEntitiesByProfileId($profileId);
            },
        ]);
    }

    public function transactions(int $profileId, LogerProfileService $profileService)
    {
        $queryParams = request()->query();

        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        return $profileService->getTransactionsByProfileId($profileId, $startDate, $endDate);
    }

    public function relationships(LogerProfileService $profileService, string $profileName = 'relationship')
    {
        $profile = $profileService->getByName(request()->user()->current_team_id, $profileName);
        return inertia('LogerProfile/ProfileView', [
            'profile' => $profile,
            'entities' => fn () =>  $profileService->getEntitiesByProfileId($profile->id),
        ]);
    }
}

/***
 * Name: Partner
 * Type: Relationship
 * kind: me and her
 * created_at: by default
 *
 * Entities:
 * - Movies
 * - Remark
 *
 * layout/display 'cards' | natural | remark | grid | events | notes
 *
 * Entity Types:
 * Category
 * Category Group
 * Meal Menu
 * Chores assigned to users
 * Plans assigned to users
 * new Board
 *  create a new board to link
 *  - visibility | show to all, show to selected, private
 *  - selected_users
 *  show in menus
 */
/***
 * Name: Diana
 * Type: Relationship
 * kind: Hija
 *
 *
 */
