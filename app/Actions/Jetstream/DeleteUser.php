<?php

namespace App\Actions\Jetstream;

use App\Models\UserDevice;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    public function __construct(
        protected DeletesTeams $deletesTeams,
        protected TransferTeamOwnership $transferTeamOwnership,
    ) {}

    /**
     * Delete the given user.
     *
     * @param  mixed  $user
     */
    public function delete($user): void
    {
        DB::transaction(function () use ($user) {
            $this->deleteTeams($user);
            $user->deleteProfilePhoto();
            $user->tokens->each->delete();
            $user->oauthTokens()->delete();
            $user->notifications()->delete();
            UserDevice::query()->where('user_id', $user->id)->delete();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
            $user->delete();
        });
    }

    /**
     * Leave the teams the user belongs to. Owned teams that other people
     * still use go to a member; the rest are deleted with all their data.
     *
     * @param  mixed  $user
     */
    protected function deleteTeams($user): void
    {
        $user->teams()->detach();

        $user->ownedTeams->each(function ($team) {
            $successor = $this->transferTeamOwnership->successorFor($team);

            if ($successor) {
                $this->transferTeamOwnership->handOver($team, $successor);

                return;
            }

            $this->deletesTeams->delete($team);
        });
    }
}
