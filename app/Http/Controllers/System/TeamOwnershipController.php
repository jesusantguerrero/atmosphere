<?php

namespace App\Http\Controllers\System;

use App\Actions\Jetstream\TransferTeamOwnership;
use App\Http\Requests\Team\TransferTeamOwnershipRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class TeamOwnershipController
{
    public function update(TransferTeamOwnershipRequest $request, Team $team, TransferTeamOwnership $transfer): RedirectResponse
    {
        $transfer->transfer($request->user(), $team, User::findOrFail($request->integer('user_id')));

        return back(303)->with('flash', [
            'banner' => __('Ownership transferred.'),
        ]);
    }
}
