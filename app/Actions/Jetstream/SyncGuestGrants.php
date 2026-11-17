<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the profiles a guest may see or work on in a household. Repeating a profile
 * keeps the strongest access: write already implies read.
 */
class SyncGuestGrants
{
    /**
     * @param  array<int, array{profile_id: int, access: string}>  $grants
     * @return Collection<int, TeamMemberGrant>
     */
    public function sync(Team $team, User $guest, array $grants): Collection
    {
        DB::transaction(function () use ($team, $guest, $grants) {
            $guest->memberGrants()->where('team_id', $team->id)->delete();

            foreach (self::normalize($grants) as $profileId => $access) {
                $guest->memberGrants()->create(['team_id' => $team->id, 'loger_profile_id' => $profileId, 'access' => $access]);
            }
        });

        return $guest->grantsIn($team);
    }

    public function revoke(Team $team, User $member): void
    {
        $member->memberGrants()->where('team_id', $team->id)->delete();
    }

    /**
     * @param  array<int, array{profile_id: int|string, access: string}>  $grants
     * @return array<int, string> access keyed by profile id
     */
    public static function normalize(array $grants): array
    {
        $byProfile = [];
        foreach ($grants as $grant) {
            $profileId = (int) $grant['profile_id'];
            if (($byProfile[$profileId] ?? null) !== TeamMemberGrant::ACCESS_WRITE) {
                $byProfile[$profileId] = $grant['access'];
            }
        }

        return $byProfile;
    }

    /**
     * The shape `/me.grants` uses.
     *
     * @param  Collection<int, TeamMemberGrant>  $grants
     * @return array<int, array{profile_id: int, access: string, name: string|null, color: string|null, shopping_list_id: int|null}>
     */
    public static function payload(Collection $grants): array
    {
        return $grants->map(fn (TeamMemberGrant $grant) => [
            'profile_id' => (int) $grant->loger_profile_id,
            'access' => $grant->access,
            'name' => $grant->profile?->name,
            'color' => $grant->profile?->color,
            'shopping_list_id' => $grant->profile?->shoppingListId(),
        ])->values()->all();
    }
}
