<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;

class CreateTeamPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-team-plans {teamId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create team plans.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $team = Team::find($this->argument('teamId'));
        $planService = new PlanService();

        foreach (PlanTypes::cases() as $planType) {
            $planService->createPlanBoard($team, $planType, $planType->name);
        }
    }
}
