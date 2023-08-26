<?php

namespace App\Providers;

use App\Domains\AppCore\Policies\FinanceAccountPolicy;
use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Models\Core\Account;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Team::class => TeamPolicy::class,
        Account::class => FinanceAccountPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('delete-account', function (User $user, Account $account) {
            return $user->id == $account->user_id;
        });
    }
}
