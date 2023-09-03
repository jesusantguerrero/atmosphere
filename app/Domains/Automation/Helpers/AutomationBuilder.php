<?php
namespace App\Domains\Automation\Helpers;

use App\Domains\Automation\Concerns\AutomationActionContract;
use App\Domains\Automation\Data\AutomationData;
use App\Domains\Automation\Enums\AutomationTaskType;

class AutomationBuilder {
    private array $tasks = [];

    public function __construct(public AutomationData $data) {}

    public static function make(AutomationData $data): self
    {
        return new self($data);
    }

    public function addAction(AutomationActionContract $action, string $type, mixed $config): AutomationBuilder {
        $this->tasks[] = [
            "name" => $action->getName(),
            "entity" => get_class($action),
            "action" => $action,
            "order" => count($this->tasks),
            "task_type" => $type,
            "values" => $config['values'] ?? []
        ];

        return $this;
    }

    public function getSchema() {
        return [
            ... $this->data->toArray(),
            "description" => implode(" ", array_map(fn ($task) => $task['action']?->label(), $this->tasks)) ,
            "integration_id" => $this->data->integration_id,
            "service_id" => $this->data->service_id,
            "sentence" => implode(" ", array_map(fn ($task) => $task['action']?->label(), $this->tasks)),
            "name" => implode(" ", array_map(fn ($task) => $task['action']?->getName(), $this->tasks)),
            "sentence" => "",
            "tasks" => $this->tasks
        ];
    }
}
