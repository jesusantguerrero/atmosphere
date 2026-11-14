<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TransferTeamOwnership
{
    /**
     * Hand the team to one of its members; the former owner stays as admin.
     */
    public function transfer(User $user, Team $team, User $newOwner): void
    {
        Gate::forUser($user)->authorize('update', $team);

        if (! $team->users()->whereKey($newOwner->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => [__('The new owner must be a member of this space.')],
            ])->errorBag('transferTeamOwnership');
        }

        $this->handOver($team, $newOwner, $user);
    }

    /**
     * The member who takes over a shared team when its owner deletes the account:
     * the longest-standing admin, otherwise the longest-standing member.
     */
    public function successorFor(Team $team): ?User
    {
        return $team->users()
            ->orderByRaw("CASE WHEN team_user.role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('team_user.created_at')
            ->orderBy('team_user.id')
            ->first();
    }

    public function handOver(Team $team, User $newOwner, ?User $formerOwner = null): void
    {
        DB::transaction(function () use ($team, $newOwner, $formerOwner) {
            $team->users()->detach($newOwner);

            $team->forceFill([
                'user_id' => $newOwner->id,
                'personal_team' => false,
            ])->save();

            if ($formerOwner) {
                $team->users()->attach($formerOwner, ['role' => 'admin']);
            }
        });
    }
}
