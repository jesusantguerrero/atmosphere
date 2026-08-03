<?php

namespace App\Domains\LogerProfile\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\LogerProfile\Data\LogerProfileData;
use App\Domains\LogerProfile\Data\ProfileEntityData;
use App\Domains\LogerProfile\Exceptions\ProfileNotFound;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Models\LogerProfileEntity;
use App\Domains\Transaction\Models\TransactionLine;

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
        // The DTO carries nullable fields (id, image_url, …) the model doesn't
        // mass-assign; restrict to the model's fillable set so create() doesn't
        // throw MassAssignmentException.
        return LogerProfile::create(
            collect($data->toArray())->only((new LogerProfile)->getFillable())->all()
        );
    }

    public function getById(int $id)
    {
        return LogerProfileData::from(LogerProfile::find($id));
    }

    public function checkByName(int $teamId, string $name)
    {
        $profile = LogerProfile::where([
            'team_id' => $teamId,
            'name' => $name,
        ])->first();

        return $profile;
    }

    public function getByName(int $teamId, string $name)
    {
        $profile = LogerProfile::where([
            'team_id' => $teamId,
            'name' => $name,
        ])->first();

        if (! $profile) {
            throw new ProfileNotFound('Profile not found');
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

    public function getTransactionsByProfileId(int $profileId, $startDate, $endDate): array
    {
        $entities = LogerProfileEntity::where([
            'profile_id' => $profileId,
            'entity_type' => Category::class,
        ])->get();

        $categories = $entities
            ->map(fn ($entity) => $entity->entity?->id)
            ->filter()
            ->values()
            ->all();

        // A profile with no category entities linked (or whose categories were
        // deleted) has nothing to report on — querying with an empty category
        // list would match every expense instead.
        if (! $categories) {
            return [
                'data' => collect(),
                'total' => 0,
            ];
        }

        $teamId = $entities->first()->team_id;

        $transactions = TransactionLine::query()
            ->byTeam($teamId)
            ->inDateFrame($startDate, $endDate)
            ->expenseCategories($categories)
            ->verified()
            ->orderByDesc('transactions.date')
            ->get();

        return [
            'data' => $transactions,
            'total' => $transactions->sum('amount'),
        ];
    }
}
