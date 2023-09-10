<?php

namespace App\Providers;

use App\Domains\Transaction\Listeners\UpdateOpenReconciliations;
use App\Events\AutomationEvent;
use App\Events\BudgetAssigned;
use App\Events\OccurrenceCreated;
use App\Events\Menu\AppCreated;
use App\Listeners\AcceptInvitation;
use App\Listeners\AutomationListener;
use App\Listeners\CheckOccurrence;
use App\Listeners\CreateBudgetMovement;
use App\Listeners\CreateBudgetCategory;
use App\Listeners\CreateBudgetTransactionMovement;
use App\Listeners\CreateOccurrenceAutomation;
use App\Listeners\CreateStartingBalance;
use App\Listeners\CreateTeamSettings;
use App\Listeners\HandleTransactionCreated;
use App\Listeners\TrashTeamSettings;
use App\Listeners\Menu\ShowInApp;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Insane\Journal\Listeners\CreateTeamAccounts;
use Insane\Journal\Events\AccountCreated;
use Insane\Journal\Events\AccountUpdated;
use Insane\Journal\Events\TransactionCreated;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;

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
            CreateTeamSettings::class
        ],
        TeamDeleted::class => [
            TrashTeamSettings::class
        ],
        TeamMemberAdded::class => [
            AcceptInvitation::class
        ],
        // Journal Events
        AccountCreated::class => [
            CreateBudgetCategory::class,
            CreateStartingBalance::class
        ],
        AccountUpdated::class => [
            CreateBudgetCategory::class,
        ],
        TransactionCreated::class => [
            CreateBudgetTransactionMovement::class,
            HandleTransactionCreated::class,
            CheckOccurrence::class,
            UpdateOpenReconciliations::class
        ],
        TransactionUpdated::class => [
            CreateBudgetTransactionMovement::class,
        ],
        TransactionDeleted::class => [
            CreateBudgetTransactionMovement::class,
        ],
        // App events
        AppCreated::class => [
            ShowInApp::class,
        ],
        AutomationEvent::class => [
            AutomationListener::class
        ],
        BudgetAssigned::class => [
            CreateBudgetMovement::class
        ],
        OccurrenceCreated::class => [
            CreateOccurrenceAutomation::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
