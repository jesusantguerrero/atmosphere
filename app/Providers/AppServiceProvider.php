<?php

namespace App\Providers;

use App\Concerns\AppMenu;
use App\Domains\AppCore\Facades\Feature;
use App\Domains\AppCore\Services\FeatureFlagService;
use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Jobs\RunTeamChecks;
use App\Models\Account;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Onboard\Facades\Onboard;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind our extended Account model to override the Journal package's Account model
        $this->app->bind(
            \Insane\Journal\Models\Core\Account::class,
            Account::class
        );

        // FeatureFlagService is a stateless helper — a singleton keeps the
        // resolved-flag runtime cache alive for the request lifecycle so
        // consecutive `Feature::active(...)` calls skip the Cache facade
        // round-trip on repeat keys.
        $this->app->singleton(FeatureFlagService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Gate::define('superadmin', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('admin', function (User $user) {
            return $user->isAdmin();
        });

        // Blade directive so views can gate blocks by flag:
        //   @feature('trends-relationships') ... @endfeature
        // Uses the auth user's context (per-user > per-team > global).
        Blade::if('feature', function (string $key): bool {
            return Feature::activeForUser($key, auth()->user());
        });

        $this->app->bindMethod([RunTeamChecks::class, 'handle'], function (RunTeamChecks $job, Application $app) {
            return $job->handle($app->make(RegisterOccurrence::class));
        });

        $this->app->singleton('menu', function () {
            return new AppMenu;
        });

        // Onboarding wizard steps (rendered by the dashboard OnboardingSteps
        // widget). Human, i18n copy via __(); ordered by the fastest path to
        // value for a family: money in → give it a plan → the people → the week.
        Onboard::addStep(__('Add your accounts'))
            ->link('/finance?panel=accounts')
            ->cta(__('Add accounts'))
            ->attributes([
                'icon' => 'fas fa-wallet',
                'name' => 'addAccounts',
                'description' => __('Add your bank, cash, and card accounts so Loger can track your money.'),
            ])
            ->completeIf(function (Team $model) {
                return $model->accounts->count() > 0;
            });

        Onboard::addStep(__('Set your first budget'))
            ->link('/budgets')
            ->cta(__('Set up budget'))
            ->attributes([
                'icon' => 'fas fa-tags',
                'name' => 'addCategories',
                'description' => __('Give every peso a job with categories that fit your family.'),
            ])
            ->completeIf(function (Team $model) {
                return $model->budgetCategories->count() > 0;
            });

        Onboard::addStep(__('Add your family'))
            ->link('/loger-profiles')
            ->cta(__('Add profiles'))
            ->excludeIf(fn (Team $model) => ! $model->isModuleEnabled('profiles'))
            ->attributes([
                'icon' => 'fas fa-users',
                'name' => 'addProfiles',
                'description' => __('Add a profile for each person and see everything linked to them.'),
            ])
            ->completeIf(function (Team $model) {
                return LogerProfile::where('team_id', $model->id)->exists();
            });

        Onboard::addStep(__('Plan your meals'))
            ->link('/meals/overview')
            ->cta(__('Plan meals'))
            ->excludeIf(fn (Team $model) => ! $model->isModuleEnabled('meals'))
            ->attributes([
                'icon' => 'fas fa-utensils',
                'name' => 'addMealPlan',
                'description' => __('Add meals to your week and build the shopping list automatically.'),
            ])
            ->completeIf(function (Team $model) {
                return $model->meals->count() > 0;
            });

    }
}
