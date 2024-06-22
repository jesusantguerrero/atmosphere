<?php

namespace App\Listeners;

use Carbon\Carbon;
use Laravel\Jetstream\Events\TeamCreated;
use App\Actions\Loger\CreateDefaultMealTypes;
use App\Domains\AppCore\Data\CoreModuleTypeEnum;
use App\Domains\Journal\Actions\AccountCatalogCreate;
use App\Domains\Journal\Actions\TransactionCategoriesCreate;

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
        $this->setAvailableModules($team);
    }

    public function setTrialPeriod($team)
    {
        $date = now()->format('Y-m-d');
        $date = Carbon::now()->addDays(14);
        $formattedDate = $date->format('Y-m-d');
        $team->trial_ends_at = $formattedDate;
        $team->save();
    }

    public function setAvailableModules($team)
    {
        foreach (CoreModuleTypeEnum::cases() as $module) {
            $team->modules()->create([
                "user_id" => $team->user_id,
                "name" => $module->name,
                "alias" => ucfirst($module->name),
                "enabled" => true,
                "created_from" => "app:console",
            ]);
        }
    }
}
