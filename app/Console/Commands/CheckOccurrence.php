<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Jobs\RunTeamChecks;
use Illuminate\Console\Command;

class CheckOccurrence extends Command
{
    protected $signature = 'app:check-occurrence {teamId}';
    protected  $description = 'check occurrence for a transaction in a team: test purposes';

    public function handle()
    {
        $team = Team::find($this->argument('teamId'));
        RunTeamChecks::dispatchSync($team->id);
    }
}
