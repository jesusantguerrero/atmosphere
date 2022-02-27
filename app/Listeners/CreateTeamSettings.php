<?php

namespace App\Listeners;

use Carbon\Carbon;
use Insane\Journal\Actions\CreateChartAccounts;
use Laravel\Jetstream\Events\TeamCreated;

class CreateTeamSettings
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TeamCreated $event)
    {
        $team = $event->team;

        if ($team->personal_team) {
            CreateChartAccounts::create($team);
            $this->setTrialPeriod($team);
        }
    }

    public function setTrialPeriod($team) {
        $date = now()->format('Y-m-d');
        $date = Carbon::now()->addDays(14);
        $formattedDate = $date->format('Y-m-d');
        $team->trial_ends_at = $formattedDate;
        $team->save();
    }
}
