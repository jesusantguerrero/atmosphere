<?php

namespace App\Listeners;

use App\Models\Setting;
use Carbon\Carbon;
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
        $settings = [
            [
                "name" => "pomodoro_template",
                "value" => json_encode(["session","longBreak"])
            ],
            [
                "name" => "pomodoro_modes",
                "value" => json_encode([
                      "session" => [
                            "name" => "Session",
                            "minutes" => 1,
                            "seconds" => 0,
                            "color" =>"red"
                      ],
                        "break" => [
                            "name" => "Break",
                            "minutes" => 5,
                            "seconds" => 0,
                            "color" => "blue"
                        ],
                        "longBreak" => [
                            "name" => "Long Break",
                            "minutes" => 15,
                            "seconds" => 0,
                            "color" => "green"
                        ]
                ])
            ]
        ];

        foreach ($settings as $setting) {
            Setting::create(array_merge($setting, [
                'user_id' => $team->user_id,
                'team_id' => $team->id
            ]));
        }

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
