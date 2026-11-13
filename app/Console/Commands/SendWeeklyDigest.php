<?php

namespace App\Console\Commands;

use App\Domains\Today\Services\WeeklyDigestService;
use App\Models\Setting;
use App\Models\Team;
use App\Notifications\WeeklyDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendWeeklyDigest extends Command
{
    protected $signature = 'loger:weekly-digest';

    protected $description = 'Send each household owner a summary of the coming week: planner events and payments due';

    public function handle(WeeklyDigestService $digests): int
    {
        Team::with('owner')->each(function (Team $team) use ($digests) {
            if (! $team->owner) {
                return;
            }

            $timeZone = Setting::getByTeam($team->id)['team_timezone'] ?? config('app.timezone');
            $digest = $digests->forTeam($team->id, Carbon::now($timeZone)->startOfDay());

            if ($digest) {
                $team->owner->notify(new WeeklyDigest($digest));
            }
        });

        return self::SUCCESS;
    }
}
