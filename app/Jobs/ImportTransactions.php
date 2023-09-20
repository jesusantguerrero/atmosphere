<?php

namespace App\Jobs;

use App\Domains\Transaction\Services\TransactionService;
use App\Models\User;
use App\Notifications\TransactionsImported;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private User $user, private mixed $file)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $results = TransactionService::importAndSave($this->user, $this->file);

        $url = "/finance/transactions?filter[status]=draft&filter[date]=$results->startDate~$results->endDate";
        $this->user->notify(new TransactionsImported($url));
    }
}
