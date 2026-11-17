<?php

namespace App\Console\Commands;

use App\Domains\LogerProfile\Services\GuestAlertService;
use App\Domains\Today\Services\WeeklyDigestService;
use App\Models\Setting;
use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use App\Notifications\GuestWeeklyDigest;
use App\Notifications\WeeklyDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendWeeklyDigest extends Command
{
    protected $signature = 'loger:weekly-digest';

    protected $description = 'Send each household owner a summary of the coming week: planner events and payments due';

    public function handle(WeeklyDigestService $digests, GuestAlertService $guestAlerts): int
    {
        Team::with('owner')->each(function (Team $team) use ($digests, $guestAlerts) {
            $timeZone = Setting::getByTeam($team->id)['team_timezone'] ?? config('app.timezone');
            $today = Carbon::now($timeZone)->startOfDay();

            $team->users()->wherePivot('role', TeamMemberGrant::GUEST_ROLE)->get()->each(function (User $guest) use ($team, $today, $guestAlerts) {
                $digest = $guestAlerts->weeklyDigestFor($team, $guest, $today);
                if ($digest) {
                    $guestAlerts->send($guest, new GuestWeeklyDigest($digest));
                }
            });

            if (! $team->owner) {
                return;
            }

            $digest = $digests->forTeam($team->id, $today);

            if ($digest) {
                $team->owner->notify(new WeeklyDigest($digest));
            }
        });

        return self::SUCCESS;
    }
}
