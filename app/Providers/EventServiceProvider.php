<?php

namespace App\Providers;

use App\Domains\Transaction\Listeners\DeleteTransactionPayment;
use App\Domains\Transaction\Listeners\UpdateOpenReconciliations;
use App\Domains\Transaction\Listeners\AutoLinkCreditCardPayment;
use App\Domains\Transaction\Listeners\UpdatePlannedTransactions;
use App\Events\AutomationEvent;
use App\Events\BudgetAssigned;
use App\Events\Menu\AppCreated;
use App\Events\OccurrenceCreated;
use App\Events\ShoppingListItemUpdated;
use App\Listeners\AcceptInvitation;
use App\Listeners\AutomationListener;
use App\Listeners\CheckOccurrence;
use App\Listeners\CreateBudgetCategory;
use App\Listeners\CreateBudgetMovement;
use App\Listeners\CreateBudgetTransactionMovement;
use App\Listeners\CreateOccurrenceAutomation;
use App\Listeners\CreateStartingBalance;
use App\Listeners\CreateTeamSettings;
use App\Listeners\HandleTransactionCreated;
use App\Listeners\Menu\ShowInApp;
use App\Listeners\PushShoppingListUpdate;
use App\Listeners\TrashTeamSettings;
use App\Listeners\UpdateBudgetAvailable;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Insane\Journal\Events\AccountCreated;
use Insane\Journal\Events\AccountUpdated;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Events\TransactionDeleted;
use Insane\Journal\Events\TransactionUpdated;
use Insane\Journal\Listeners\CreateTeamAccounts;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamMemberAdded;

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
        ],
        AccountUpdated::class => [
            CreateBudgetCategory::class,
        ],
        TransactionCreated::class => [
            CreateBudgetTransactionMovement::class,
            HandleTransactionCreated::class,
            CheckOccurrence::class,
            UpdatePlannedTransactions::class,
            UpdateOpenReconciliations::class,
            UpdateBudgetAvailable::class,
            AutoLinkCreditCardPayment::class,
        ],
        TransactionUpdated::class => [
            CreateBudgetTransactionMovement::class,
            UpdateBudgetAvailable::class,
            UpdatePlannedTransactions::class,
            AutoLinkCreditCardPayment::class,
        ],
        TransactionDeleted::class => [
            CreateBudgetTransactionMovement::class,
            UpdateBudgetAvailable::class,
            DeleteTransactionPayment::class,
            UpdatePlannedTransactions::class,
        ],
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
        ShoppingListItemUpdated::class => [
            PushShoppingList