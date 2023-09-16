<?php

namespace App\Console\Commands;

use App\Domains\Housing\Contracts\OccurrenceNotifyTypes;
use App\Domains\Housing\Models\OccurrenceCheck;
use App\Models\User;
use App\Notifications\OccurrenceAlert;
use Illuminate\Console\Command;
use Kreait\Laravel\Firebase\Facades\Firebase;

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
        $occurrencesOnLast = OccurrenceCheck::getForNotificationType(OccurrenceNotifyTypes::LAST);
        $occurrencesOnAvg = OccurrenceCheck::getForNotificationType(OccurrenceNotifyTypes::AVG);

        $this->sendNotifications($occurrencesOnLast);
        $this->sendNotifications($occurrencesOnAvg);
    }

    public function sendNotifications($occurrences) {
        foreach ($occurrences as $occurrence) {
            User::find($occurrence->user_id)->notify(new OccurrenceAlert($occurrence));
        }
    }
}
