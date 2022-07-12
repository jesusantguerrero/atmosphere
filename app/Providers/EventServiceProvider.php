<?php

namespace App\Providers;

use App\Listeners\CreateBudgetCategory;
use App\Listeners\CreateTeamSettings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Insane\Journal\Listeners\CreateTeamAccounts;
use Insane\Journal\Events\AccountCreated;
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
            CreateTeamSettings::class,
            // CreateTeamAccounts::class
        ],
        AccountCreated::class => [
            CreateBudgetCategory::class
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
