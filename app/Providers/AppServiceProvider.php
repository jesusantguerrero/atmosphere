<?php

namespace App\Providers;

use App\Models\Team;
use Illuminate\Support\ServiceProvider;
use Spatie\Onboard\Facades\Onboard;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        Onboard::addStep('Step 1: confirm your email')
        ->link('/user/profile')
        ->attributes([
            'icon' => 'fas fa-envelope-open-text',
            'name' => 'confirmEmail',
            'description' => 'Validate your email address',
        ])
        ->cta('Resend confirmation')
        ->completeIf(function (Team $model) {

            return (bool) $model->owner->email_verified_at;
        });

        Onboard::addStep('Step 2: Add accounts')
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

        Onboard::addStep('Step 3: Add budget categories')
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

        Onboard::addStep('Step 4: Add meal plan')
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
