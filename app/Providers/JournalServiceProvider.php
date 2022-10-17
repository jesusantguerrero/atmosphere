<?php

namespace App\Providers;


use App\Actions\Journal\DeleteAccount;
use Illuminate\Support\ServiceProvider;
use Insane\Journal\Journal;

class JournalServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Journal::deleteAccountUsing(DeleteAccount::class);
    }
}
