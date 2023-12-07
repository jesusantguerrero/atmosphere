<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Notifications\OccurrenceAlert;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Housing\Contracts\OccurrenceNotifyTypes;

class MakeOccurrenceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:occurrence-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make reminders of occurrence';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $occurrencesOnLast = Occurrence::getForNotificationType(OccurrenceNotifyTypes::LAST);
        $occurrencesOnAvg = Occurrence::getForNotificationType(OccurrenceNotifyTypes::AVG);

        $this->sendNotifications($occurrencesOnLast, OccurrenceNotifyTypes::LAST);
        $this->sendNotifications($occurrencesOnAvg, OccurrenceNotifyTypes::AVG);
    }

    public function sendNotifications($occurrences, $type)
    {
        foreach ($occurrences as $occurrence) {
            User::find($occurrence->user_id)->notify(new OccurrenceAlert($occurrence, $type));
        }
    }
}
