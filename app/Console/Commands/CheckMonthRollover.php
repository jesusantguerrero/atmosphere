<?php

namespace App\Console\Commands;

use DateTime;
use App\Models\Team;

use Illuminate\Console\Command;
use App\Domains\Budget\Services\BudgetRolloverService;

class CheckMonthRollover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-month-rollover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check month roll over for all teams';

    /**
     * Execute the console command.
     */
    public function handle(BudgetRolloverService $rolloverService)
    {

        $teams = Team::with('timezone')->without(['settings'])->get();
        $now = now();
        foreach ($teams as $team) {
            $timezone = $team["timezone"] ?? null;
            if (!$timezone) continue;

            $now->setTimezone($timezone['value']);
            if ($now->format('H:i') === '00:00' && $now->format('Y-m-d')) {
                $previousMonth = $now->subMonth()->startOf('month')->format('Y-m');
                $rolloverService->startFrom($team->id, $previousMonth);
            }
        }
    }
}
