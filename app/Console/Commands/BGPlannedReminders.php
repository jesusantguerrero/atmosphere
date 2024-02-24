<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Notifications\PlannedAlert;
use App\Notifications\OccurrenceAlert;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Housing\Contracts\OccurrenceNotifyTypes;
use App\Domains\Transaction\Services\PlannedTransactionService;

class BGPlannedReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bg:planned-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make reminders of planned transactions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(PlannedTransactionService $plannedService)
    {
        $this->sendNotifications($plannedService->getForNotificationType());
    }

    public function sendNotifications($occurrences)
    {
        foreach ($occurrences as $occurrence) {
            User::find($occurrence->user_id)->notify(new PlannedAlert($occurrence));
        }
    }
}
