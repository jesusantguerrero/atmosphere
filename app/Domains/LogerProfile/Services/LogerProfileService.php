<?php

namespace App\Domains\LogerProfile\Services;

use App\Domains\LogerProfile\Data\LogerProfileData;
use App\Domains\LogerProfile\Data\ProfileEntityData;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Models\LogerProfileEntity;

class LogerProfileService {

    public function list($teamId) {
        return LogerProfile::select([
            "name",
            "id"
        ])->where([
            "team_id" => $teamId
        ])->get();
    }

    public function create(LogerProfileData $data) {
        return LogerProfile::create($data->toArray());
    }

    public function getById(int $id) {
        return LogerProfileData::from(LogerProfile::find($id));
    }

    public function addProfileEntity(ProfileEntityData $profileEntityData) {
        LogerProfileEntity::create($profileEntityData->toArray());
    }

    public function getEntitiesByProfileId(int $profileId) {
        return ProfileEntityData::collection(LogerProfileEntity::where([
            "profile_id" => $profileId
        ])->get());
    }
}
