<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessGmail;
use App\Libraries\GoogleService;
use App\Models\Integrations\Automation;

class AutomationController extends BaseController
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
            return ["done" => $automation];
        } else {
            $automations = Automation::where([
                "automation_recipe_id" => 1
            ])->get();

            if (count($automations)) {
                foreach ($automations as $automation) {
                    ProcessGmail::dispatch($automation);
                }
            }

            return $automations;
        }
    }
}
