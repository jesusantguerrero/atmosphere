<?php

namespace Database\Seeders;

use App\Domains\Automation\Models\AutomationRecipe;
use App\Domains\Automation\Models\AutomationService;
use App\Domains\Automation\Models\AutomationTask;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Automation\Services\LogerAutomationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AutomationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AutomationService::truncate();
        AutomationTask::truncate();
        AutomationRecipe::truncate();
        AutomationTaskAction::truncate();

        $services = LogerAutomationService::services();

        foreach ($services as $serviceName => $service) {
            DB::table('automation_services')->insert([
                'name' => $serviceName,
                'label' => $service['label'] ?? $serviceName,
                'entity' => $service['entity'],
                'type' => $service['type'],
                'description' => $service['description'],
                'logo' => $service['logo'],
            ]);

            $serviceId = DB::getPdo()->lastInsertId();
            LogerAutomationService::setupService($serviceId,$service);
        }
    }
}
