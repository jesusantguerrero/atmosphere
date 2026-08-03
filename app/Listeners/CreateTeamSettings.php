<?php

namespace App\Listeners;

use App\Actions\Loger\CreateDefaultMealTypes;
use App\Domains\AppCore\Data\CoreModuleTypeEnum;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetDefaultCategory;
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
    ) {}

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
        $this->createHouseholdProfiles($team);
        $this->setBudgetSplitDefaults($team);
    }

    public function setBudgetSplitDefaults($team): void
    {
        $mapping = [
            'savings_general' => BudgetDefaultCategory::ROLE_SAVINGS,
            'personal_spending' => BudgetDefaultCategory::ROLE_SPENDING,
        ];

        foreach ($mapping as $displayId => $role) {
            $category = Category::where([
                'team_id' => $team->id,
                'display_id' => $displayId,
            ])->first();

            if (! $category) {
                continue;
            }

            BudgetDefaultCategory::updateOrCreate(
                ['team_id' => $team->id, 'role' => $role],
                ['category_id' => $category->id]
            );
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

    public function createHouseholdProfiles($team): void
    {
        // Household names collected at Space Setup (JSON so odd names
        // survive the settings-persistence round-trip). Empty when the
        // user didn't opt into Profiles.
        $raw = request()->input('members', '');
        if (empty($raw)) {
            return;
        }
        $names = json_decode((string) $raw, true);
        if (! is_array($names)) {
            return;
        }
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            LogerProfile::create([
                'team_id' => $team->id,
                'user_id' => $team->user_id,
                'name' => $name,
            ]);
        }
    }

    public function setAvailableModules($team)
    {
        // Finance is always on (the door). Any other module the user opted into
        // at Space Setup is enabled here; the rest stay off. The selection rides
        // the same onboarding request, so request() is the source of truth. When
        // absent (API/team creation without the picker) only Finance is on — the
        // previous default, preserved.
        $rawModules = request()->input('modules', '');
        $selected = collect(is_array($rawModules) ? $rawModules : explode(',', (string) $rawModules))
            ->map(fn ($name) => strtolower(trim((string) $name)))
            ->filter()
            ->all();

        foreach (CoreModuleTypeEnum::cases() as $module) {
            $team->modules()->create([
                'user_id' => $team->user_id,
                'name' => $module->name,
                'alias' => ucfirst($module->name),
                'enabled' => $module === CoreModuleTypeEnum::Finance || in_array(strtolower($module->name), $selected, true),
                'created_from' => 'app:console',
            ]);
        }
    }
}
