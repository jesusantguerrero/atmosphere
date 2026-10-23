<?php

namespace App\Domains\LogerProfile\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\LogerProfile\Data\LogerProfileData;
use App\Domains\LogerProfile\Data\ProfileEntityData;
use App\Domains\LogerProfile\Exceptions\ProfileNotFound;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Models\LogerProfileEntity;
use App\Domains\Transaction\Models\TransactionLine;
use App\Models\User;

class LogerProfileService
{
    public function ensurePersonalProfile(User $user): LogerProfile
    {
        $identity = ['team_id' => $user->current_team_id, 'personal_user_id' => $user->id];
        $existing = LogerProfile::query()->where($identity)->first();
        if ($existing) {
            return $existing;
        }

        return LogerProfile::unguarded(fn () => LogerProfile::query()->firstOrCreate($identity, [
            'user_id' => $user->id,
            'name' => $user->name,
        ]));
    }

    public function list($teamId)
    {
        return LogerProfile::select([
            'name',
            'id',
            'personal_user_id',
        ])->where([
            'team_id' => $teamId,
        ])->orderByRaw('CASE WHEN personal_user_id = ? THEN 0 ELSE 1 END', [auth()->id() ?? 0])->orderBy('name')->get();
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

    /**
     * 404s unless the profile belongs to the given team.
     */
    public function ensureTeamProfile(int $teamId, int $profileId): void
    {
        abort_unless(LogerProfile::where(['team_id' => $teamId, 'id' => $profileId])->exists(), 404);
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

        $teamId = LogerProfile::findOrFail($profileId)->team_id;

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
