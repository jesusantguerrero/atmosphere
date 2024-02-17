<?php

namespace App\Providers;

use App\Events\BudgetAssigned;
use App\Events\AutomationEvent;
use App\Events\Menu\AppCreated;
use App\Events\OccurrenceCreated;
use App\Listeners\Menu\ShowInApp;
use App\Listeners\CheckOccurrence;
use App\Listeners\AcceptInvitation;
use App\Listeners\TrashTeamSettings;
use App\Listeners\AutomationListener;
use App\Listeners\CreateTeamSettings;
use Illuminate\Auth\Events\Registered;
use App\Listeners\CreateBudgetCategory;
use App\Listeners\CreateBudgetMovement;
use App\Listeners\CreateStartingBalance;
use Insane\Journal\Events\AccountCreated;
use Insane\Journal\Events\AccountUpdated;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use App\Listeners\HandleTransactionCreated;
use App\Listeners\CreateOccurrenceAutomation;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Listeners\CreateTeamAccounts;
use App\Listeners\CreateBudgetTransactionMovement;
use App\Domains\Transaction\Listeners\UpdateOpenReconciliations;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TeamCreated::class => [
            CreateTeamAccounts::class,
            CreateTeamSettings::class,
        ],
        TeamDeleted::class => [
            TrashTeamSettings::class,
        ],
        TeamMemberAdded::class => [
            AcceptInvitation::class,
        ],
        // Journal Events
        AccountCreated::class => [
            CreateBudgetCategory::class,
            CreateStartingBalance::class,
            CreateBudgetMovement::class,
        ],
        AccountUpdated::class => [
            CreateBudgetCategory::class,
            CreateBudgetMovement::class,
        ],
        TransactionCreated::class => [
            CreateBudgetTransactionMovement::class,
            HandleTransactionCreated::class,
            CheckOccurrence::class,
            UpdateOpenReconciliations::class,
            CreateBudgetMovement::class,
        ],
        TransactionUpdated::class => [
            CreateBudgetTransactionMovement::class,
            CreateBudgetMovement::class,
        ],
        TransactionDeleted::class => [
            CreateBudgetTransactionMovement::class,
            CreateBudgetMovement::class,
        ],
        // App events
        AppCreated::class => [
            ShowInApp::class,
        ],
        AutomationEvent::class => [
            AutomationListener::class,
        ],
        BudgetAssigned::class => [
            CreateBudgetMovement::class,
        ],
        OccurrenceCreated::class => [
            CreateOccurrenceAutomation::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
