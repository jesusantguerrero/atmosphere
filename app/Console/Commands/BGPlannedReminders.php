<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Notifications\PlannedAlert;
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

    public function sendNotifications($plannedTransactions)
    {
        foreach ($plannedTransactions as $transaction) {
            User::find($transaction->user_id)->notify(new PlannedAlert($transaction));
        }
    }
}
