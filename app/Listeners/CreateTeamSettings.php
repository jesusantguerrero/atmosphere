<?php

namespace App\Listeners;

use App\Actions\Loger\CreateDefaultMealTypes;
use Carbon\Carbon;
use Insane\Journal\Actions\CreateChartAccounts;
use Insane\Journal\Actions\CreateTransactionCategories;
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

        (new CreateChartAccounts)->create($team);
        (new CreateTransactionCategories)->create($team);
        (new CreateDefaultMealTypes)->setup($team->id, $team->user_id);

        if ($team->personal_team) {
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
