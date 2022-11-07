<?php

namespace App\Providers;

use App\Events\AutomationEvent;
use App\Events\BudgetAssigned;
use App\Events\OccurrenceCreated;
use App\Listeners\AcceptInvitation;
use App\Listeners\AutomationListener;
use App\Listeners\CreateBudgetMovement;
use App\Listeners\CreateBudgetCategory;
use App\Listeners\CreateBudgetTransactionMovement;
use App\Listeners\CreateOccurrenceAutomation;
use App\Listeners\CreateStartingBalance;
use App\Listeners\CreateTeamSettings;
use App\Listeners\HandleTransactionCreated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Insane\Journal\Listeners\CreateTeamAccounts;
use Insane\Journal\Events\AccountCreated;
use Insane\Journal\Events\TransactionCreated;
use Laravel\Jetstream\Events\TeamCreated;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TeamCreated::class => [
            CreateTeamAccounts::class,
            CreateTeamSettings::class
        ],
        TeamMemberAdded::class => [
            AcceptInvitation::class
        ],
        // Journal Events
        AccountCreated::class => [
            CreateBudgetCategory::class,
            CreateStartingBalance::class
        ],
        TransactionCreated::class => [
            CreateBudgetTransactionMovement::class,
            HandleTransactionCreated::class
        ],
        // App events
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
    public function boot()
    {
        //
    }
}
