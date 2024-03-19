<?php

namespace App\Domains\LogerProfile\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Data\LogerProfileData;
use App\Domains\Transaction\Models\TransactionLine;
use App\Domains\LogerProfile\Data\ProfileEntityData;
use App\Domains\LogerProfile\Models\LogerProfileEntity;
use App\Domains\LogerProfile\Exceptions\ProfileNotFound;
use App\Domains\Transaction\Services\TransactionService;

class LogerProfileService
{
    public function list($teamId)
    {
        return LogerProfile::select([
            'name',
            'id',
        ])->where([
            'team_id' => $teamId,
        ])->get();
    }

    public function create(LogerProfileData $data)
    {
        return LogerProfile::create($data->toArray());
    }

    public function getById(int $id)
    {
        return LogerProfileData::from(LogerProfile::find($id));
    }

    public function getByName(int $teamId, string $name)
    {
        $profile = LogerProfile::where([
            "team_id" => $teamId,
            "name" => $name,
        ])->first();

        if (!$profile) {
            throw new ProfileNotFound("Profile not found");
        }

        return LogerProfileData::from($profile);
    }

    public function addProfileEntity(ProfileEntityData $profileEntityData)
    {
        LogerProfileEntity::create($profileEntityData->toArray());
    }

    public function getEntitiesByProfileId(int $profileId)
    {
        return ProfileEntityData::collect(LogerProfileEntity::where([
            'profile_id' => $profileId,
        ])->get());
    }

    public function getTransactionsByProfileId(int $profileId, $startDate, $endDate)
    {
        $entities =  LogerProfileEntity::where([
            'profile_id' => $profileId,
            'entity_type' => Category::class
        ])->get();

        $categories = $entities->map(fn ($entity) => $entity->entity->id)->all();

        $teamId = $entities[0]->team_id;

        $transactions = TransactionLine::query()
            ->byTeam($teamId)
            ->inDateFrame($startDate, $endDate)
            ->expenseCategories($categories)
            ->verified()
            ->orderByDesc('transactions.date')
            ->get();

        return [
            "data" => $transactions,
            "total" => $transactions->sum('amount'),
        ];
    }
}
