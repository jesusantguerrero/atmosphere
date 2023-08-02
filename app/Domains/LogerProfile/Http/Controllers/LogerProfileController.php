<?php

namespace App\Domains\LogerProfile\Http\Controllers;

use App\Domains\LogerProfile\Data\LogerProfileData;
use App\Domains\LogerProfile\Services\LogerProfileService;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasEnrichedRequest;

class LogerProfileController extends Controller
{
    use HasEnrichedRequest;

    public function index(LogerProfileService $profileService) {
        return inertia("LogerProfile/Index", [
            "profiles" => $profileService->list(auth()->user()->current_team_id),
        ]);
    }


    public function store(LogerProfileService $profileService) {

         $profileService->create(
            LogerProfileData::from($this->getPostData())
        );
    }
}
