<?php

namespace App\Domains\LogerProfile\Services;

use App\Domains\LogerProfile\Data\LogerProfileData;
use App\Domains\LogerProfile\Models\LogerProfile;

class LogerProfileService {

    public function list($teamId) {
        return LogerProfile::where([
            "team_id" => $teamId
        ])->get();
    }

    public function create(LogerProfileData $data) {
        return LogerProfile::create($data->toArray());
    }
}
