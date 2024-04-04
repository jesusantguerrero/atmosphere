<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Insane\Journal\Models\Core\Transaction;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Budget\Services\BudgetRolloverService;

class UpdateBudgetAvailable implements ShouldQueue
{
    protected $formData;


    public function __construct()
    {

    }

    public function handle($event)
    {
        if ($event->transactions->status == Transaction::STATUS_VERIFIED) {
            $teamId = $event->transaction->team_id;
            $date = $event->transaction->date;
            (new BudgetRolloverService(new BudgetCategoryService()))->startFrom($teamId, substr($date, 0, 7));
        }
    }
}
