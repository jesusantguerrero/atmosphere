<?php

namespace App\Console\Commands;

use App\Domains\Budget\Services\LoanReminderService;
use App\Domains\Transaction\Services\PlannedTransactionService;
use App\Models\User;
use App\Notifications\PlannedAlert;
use Illuminate\Console\Command;

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
     */
    public function handle(PlannedTransactionService $plannedService, LoanReminderService $loanReminders): int
    {
        $loanReminders->sendDueReminders();
        $this->sendNotifications($plannedService->getForNotificationType());

        return self::SUCCESS;
    }

    public function sendNotifications($plannedTransactions)
    {
        foreach ($plannedTransactions as $transaction) {
            User::find($transaction->user_id)->notify(new PlannedAlert($transaction));
        }
    }
}
