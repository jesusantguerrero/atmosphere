<?php

namespace App\Listeners;

use App\Actions\Loger\CreateDefaultMealTypes;
use App\Domains\Journal\Actions\AccountCatalogCreate;
use App\Domains\Journal\Actions\TransactionCategoriesCreate;
use Carbon\Carbon;
use Laravel\Jetstream\Events\TeamCreated;

class CreateTeamSettings
{
    public function __construct(
        public AccountCatalogCreate $accountCatalog,
        public TransactionCategoriesCreate $transactionCategories,
        public CreateDefaultMealTypes $createDefaultMealTypes
    ) {

    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TeamCreated $event)
    {
        $team = $event->team;

        $this->accountCatalog->createChart($team);
        $this->accountCatalog->createCatalog($team);
        $this->transactionCategories->create($team);
        $this->createDefaultMealTypes->setup($team->id, $team->user_id);

        if ($team->personal_team) {
            $this->setTrialPeriod($team);
        }
    }

    public function setTrialPeriod($team)
    {
        $date = now()->format('Y-m-d');
        $date = Carbon::now()->addDays(14);
        $formattedDate = $date->format('Y-m-d');
        $team->trial_ends_at = $formattedDate;
        $team->save();
    }
}
