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
    protected $signature = 'app:automation-check {--backfill : Backfill mode to process older emails}';

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
        $automations = Automation::where('is_background', true)
            ->where('status', true)
            ->get();
        $backfillMode = $this->option('backfill');

        foreach ($automations as $automation) {
            $eventData = $backfillMode ? ['backfill' => true] : null;
            LogerAutomationService::run($automation, $eventData);
        }
    }
}
