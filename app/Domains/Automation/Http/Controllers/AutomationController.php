<?php

namespace App\Domains\Automation\Http\Controllers;

use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Services\GoogleService;
use Freesgen\Atmosphere\Http\InertiaController;

class AutomationController extends InertiaController
{
    public function __construct()
    {
        $this->model = new Automation();
        $this->searchable = ['name'];
        $this->validationRules = [];
    }

    protected function afterSave($postData, $resource): void
    {
        $resource->saveTasks($postData['tasks']);
    }

    public function run($automationId)
    {
        $automation = Automation::find($automationId);
        if ($automation) {
            $service = $automation->recipe->name;
            GoogleService::$service($automation->id, true);

            return ['done' => $automation];
        }
    }

    public function runAll()
    {
        $automationTasks = Automation::where([
            // "team_id" => auth()->user()->team_id,
            'user_id' => auth()->user()->id,
        ])->get();

        foreach ($automationTasks as $automation) {
            $automation->dispatch();
        }

        return $automationTasks;
    }
}
