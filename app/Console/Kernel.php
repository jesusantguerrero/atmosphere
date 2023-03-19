<?php

namespace App\Console;

use App\Models\Integrations\Automation;
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
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $scheduleTime = config('app.schedule_time');

        $schedule->command("automation:check")->everyMinute()->runInBackground();
        $schedule->command('loger:occurrence-reminders')->daily()->runInBackground();
        if (config('app.demo')) {
            if ($scheduleTime) {
                $schedule->command("demo:setup")->dailyAt($scheduleTime)->runInBackground();
            } else {
                $schedule->command("demo:setup")->everyTwoHours()->runInBackground();
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
