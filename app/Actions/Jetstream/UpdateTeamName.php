<?php

namespace App\Actions\Jetstream;

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use App\Domains\AppCore\Models\CoreModule;
use Laravel\Jetstream\Contracts\UpdatesTeamNames;

class UpdateTeamName implements UpdatesTeamNames
{
    /**
     * Validate and update the given team's name.
     *
     * @param  mixed  $user
     * @param  mixed  $team
     * @return void
     */
    public function update($user, $team, array $input)
    {
        Gate::forUser($user)->authorize('update', $team);

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
        ])->validateWithBag('updateTeamName');

        $team->forceFill([
            'name' => $input['name'],
        ])->save();

        $entryData = [
            'user_id' => $user->id,
            'team_id' => $team->id,
        ];

        $settingData = array_filter($input, fn($row) => $row != "modules", ARRAY_FILTER_USE_KEY);

        Setting::storeBulk($settingData, $entryData);
        CoreModule::updateBulk($input['modules']);
    }
}
