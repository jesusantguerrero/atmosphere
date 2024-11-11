<?php

namespace App\Providers;

use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Transaction;
use App\Domains\Journal\Policies\CategoryPolicy;
use App\Domains\Journal\Policies\TransactionPolicy;
use Insane\Journal\Models\Accounting\Reconciliation;
use App\Domains\AppCore\Policies\FinanceAccountPolicy;
use App\Domains\Transaction\Policies\ReconciliationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Team::class => TeamPolicy::class,
        Account::class => FinanceAccountPolicy::class,
        Reconciliation::class => ReconciliationPolicy::class,
        Transaction::class => TransactionPolicy::class,
        Category::class => CategoryPolicy::class,
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
