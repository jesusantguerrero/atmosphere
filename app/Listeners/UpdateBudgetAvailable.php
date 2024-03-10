<?php

namespace App\Listeners;

use App\Events\BudgetAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        $teamId = $event->transaction->team_id;
        $date = $event->transaction->date;
        (new BudgetRolloverService(new BudgetCategoryService()))->startFrom($teamId, substr($date, 0, 7));
    }
}
