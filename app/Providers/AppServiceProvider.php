<?php

namespace App\Providers;

use App\Concerns\AppMenu;
use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Jobs\RunTeamChecks;
use App\Models\Team;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Spatie\Onboard\Facades\Onboard;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        $this->app->bindMethod([RunTeamChecks::class, 'handle'], function (RunTeamChecks $job, Application $app) {
            return $job->handle($app->make(RegisterOccurrence::class));
        });

        $this->app->singleton('menu', function (Application $app) {
            return new AppMenu();
        });

        Onboard::addStep('Step 1: Add accounts')
            ->link('/finance')
            ->cta('Go to accounts')
            ->attributes([
                'icon' => 'fas fa-credit-card',
                'name' => 'addAccounts',
                'description' => 'Create your accounts',
                'name' => 'addAccounts',

            ])
            ->completeIf(function (Team $model) {
                return $model->accounts->count() > 0;
            });

        Onboard::addStep('Step 2: Add budget categories')
            ->link('/budgets')
            ->cta('Go to finance/budget')
            ->attributes([
                'icon' => 'fas fa-tags',
                'name' => 'AddCategories',
                'description' => 'Structure your budgets with category groups and categories',
            ])
            ->completeIf(function (Team $model) {
                return $model->budgetCategories->count() > 0;
            });

        Onboard::addStep('Step 3: Add meal plan')
            ->link('/meal-planner')
            ->cta('Go to meal/plan')
            ->attributes([
                'icon' => 'fas fa-credit-card',
                'name' => 'AddMealPlan',
                'description' => 'Organize your meals',
            ])
            ->completeIf(function (Team $model) {
                return $model->meals->count() > 0;
            });

    }
}
