<?php

namespace App\Console\Commands;

use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Services\LogerAutomationService;
use Illuminate\Console\Command;

class AutomationCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:automation-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check automatic flows of users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $automations = Automation::whereNotNull('is_background')->get();
        foreach ($automations as $automation) {
            LogerAutomationService::run($automation);
        }
    }
}
