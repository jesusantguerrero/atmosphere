<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $scheduleTime = config('app.schedule_time');

        $schedule->command('app:automation-check')->everyMinute()->runInBackground();
        $schedule->command('app:occurrence-reminders')->daily()->runInBackground();
        $schedule->command('app:check-month-rollover')->everyMinute()->runInBackground();
        $schedule->command('app:planned-from-budget')->monthly()->runInBackground();
        $schedule->command('bg:generate-billing-cycles')->daily()->runInBackground();
        $schedule->command('bg:planned-reminders')->monthly()->runInBackground();
        if (config('app.demo')) {
            if ($scheduleTime) {
                $schedule->command('app:demo-reset')->dailyAt($scheduleTime)->runInBackground();
            } else {
                $schedule->command('app:demo-reset')->everyTwoHours()->runInBackground();
            }
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
