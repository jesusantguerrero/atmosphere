<?php

namespace App\Domains\Automation\Services;

use Google\Service\Gmail;
use App\Domains\Integration\Actions\BHD;
use App\Domains\Automation\Models\Automation;
use App\Domains\Integration\Actions\BHDAlert;
use App\Domains\Integration\Actions\APAP\APAP;
use App\Domains\Automation\Models\AutomationTask;
use App\Domains\Integration\Actions\MealPlanAutomation;
use App\Domains\Integration\Actions\OccurrenceAutomation;
use App\Domains\Integration\Actions\TransactionCreateEntry;

class LogerAutomationService
{
    const MEAL_PLAN_LIKED = 'mealPlanLiked';

    const TRANSACTION_CREATED = 'transactionCreated';

    const TRANSACTION_OCCURRENCE = 'transactionsFetch';

    public static function run(Automation $automation, $eventData = null)
    {
        echo "starting $automation->name with $automation->id \n";
        $tasks = $automation->tasks;
        $trigger = $automation->triggerTask;

        foreach ($tasks as $index => $task) {
            if ($index == 0) {
                $lastData = $eventData;
                $previousTask = null;
            }
            $entity = $task->entity;
            $lastData = $entity::handle($automation, $lastData, $task, $previousTask, $trigger);
            if (!$lastData) {
                break;
            }
            $previousTask = $task;
        }
    }

    public static function setupService($serviceId, $service)
    {
        $taskTypes = [
            'triggers' => 'trigger',
            'components' => 'component',
            'actions' => 'action'
        ];
        foreach ($taskTypes as $taskTypeName => $taskType) {
            if (isset($service[$taskType])) {
                foreach ($service[$taskType] as $task) {
                    AutomationTask::createTask(
                        $serviceId,
                        $task,
                        $service,
                        $taskTypeName
                    );
                }
            }
        }
    }

    public static function services()
    {
        return [
            'Gmail' => [
                'name' => 'Gmail',
                'logo' => '/images/gmail.png',
                'entity' => Gmail::class,
                'description' => 'Gmail is a free web-based email service that gives you access to your Gmail account. You can use Gmail to send and receive email, manage your contacts, read your emails, and much more. Gmail is free for all users, and you can get started right away.',
                'fields' => json_encode([
                    'index' => [
                        'type' => 'integer',
                        'label' => 'Index',
                        'required' => true,
                    ],
                    'from' => [
                        'type' => 'string',
                        'label' => 'From',
                        'required' => true,
                    ],
                    'subject' => [
                        'type' => 'string',
                        'label' => 'Subject',
                        'required' => true,
                    ],
                    'messageId' => [
                        'type' => 'string',
                        'label' => 'Message ID',
                        'required' => true,
                    ],
                    'id' => [
                        'type' => 'string',
                        'label' => 'ID',
                        'required' => true,
                    ],
                    'threadId' => [
                        'type' => 'string',
                        'label' => 'Thread ID',
                        'required' => true,
                    ],
                    'historyId' => [
                        'type' => 'string',
                        'label' => 'History ID',
                        'required' => true,
                    ],
                    'date' => [
                        'type' => 'string',
                        'label' => 'Date',
                        'required' => true,
                    ],
                    'message' => [
                        'type' => 'text',
                        'label' => 'Message',
                        'required' => true,
                    ],
                ]),
                'triggers' => [
                    [
                        'name' => 'received',
                        'label' => 'New email',
                        'entity' => 'App\Actions\Integrations\Gmail',
                        'description' => 'When a new email is received',
                        'config' => json_encode([
                            'conditionType' => [
                                'title' => 'Condition type',
                                'type' => 'select',
                                'options' => [
                                    'from',
                                    'to',
                                    'subject',
                                    'includes',
                                    'custom',
                                ],
                            ],
                            'value' => [
                                'title' => 'search text',
                                'type' => 'input',
                            ],
                        ]),
                        'accepts_config' => true,
                    ],
                ],
                'type' => 'external',
            ],
            'BHD' => [
                'name' => 'BHD',
                'entity' => BHD::class,
                'logo' => '/images/bhd.png',
                'description' => 'BHD bank',
                'components' => [
                    [
                        'label' => 'Parse Alert',
                        'name' => 'parseMessage',
                        'entity' => BHD::class,
                        'description' => 'Parse an email alert',
                        'config' => json_encode(BHDAlert::getSchema()),

                    ],
                ],
                'type' => 'internal',
            ],
            'APAP' => [
                'name' => 'BHD',
                'entity' => APAP::class,
                'logo' => '/images/bhd.png',
                'description' => 'BHD bank',
                'components' => [
                    [
                        'label' => 'Parse Alert',
                        'name' => 'parseMessage',
                        'entity' => BHD::class,
                        'description' => 'Parse an email alert',
                        'config' => json_encode(BHDAlert::getSchema()),

                    ],
                ],
                'type' => 'internal',
            ],
            'transactions' => [
                'name' => 'transactions',
                'label' => 'Transactions',
                'logo' => '/images/transactions.png',
                'entity' => TransactionCreateEntry::class,
                'description' => 'BHD bank',
                'fields' => json_encode(TransactionCreateEntry::fieldConfig()),
                'triggers' => [
                    [
                        'name' => 'transactionCreated',
                        'label' => 'Transaction Created',
                        'entity' => MealPlanAutomation::class,
                        'description' => 'When a transaction is created',
                        'config' => json_encode([
                            'field' => [
                                'title' => 'field',
                                'type' => 'select',
                                'options' => [array_keys(TransactionCreateEntry::fieldConfig())],
                            ],
                            'conditionType' => [
                                'title' => 'Condition',
                                'type' => 'select',
                                'options' => ['equal', 'includes', 'starts', 'ends', 'not'],
                            ],
                            'value' => [
                                'title' => 'search text',
                                'type' => 'input',
                            ],
                            'connector' => [
                                'title' => 'Connector',
                                'type' => 'select',
                                'options' => ['AND', 'OR'],
                            ],
                        ]),
                        'accepts_config' => true,
                    ],
                ],
                'actions' => [
                    [
                        'name' => 'createTransaction',
                        'label' => 'Create transaction',
                        'entity' => TransactionCreateEntry::class,
                        'description' => 'Create a new transaction',
                        'config' => json_encode(TransactionCreateEntry::fieldConfig()),
                        'accepts_config' => true,
                    ],
                ],
                'type' => 'internal',
            ],
            'mealPlanner' => [
                'name' => 'meal_planner',
                'label' => 'Meal Planner',
                'logo' => '/images/meal-planner.png',
                'entity' => MealPlanAutomation::class,
                'description' => 'Meal Plans',
                'fields' => [
                    'meal_id' => [
                        'type' => 'id',
                        'required' => true,
                    ],
                    'date' => [
                        'type' => 'date',
                        'required' => true,
                    ],
                    'meal_type_id' => [
                        'type' => 'id',
                    ],
                    'metaData' => [
                        'type' => 'json',
                        'required' => false,
                    ],
                ],
                'triggers' => [
                    [
                        'name' => 'mealPlanLiked',
                        'label' => 'Meal Plan Liked',
                        'entity' => MealPlanAutomation::class,
                        'description' => 'When a meal plan is liked',
                        'config' => json_encode([
                            'meal_id' => [
                                'type' => 'id',
                                'required' => true,
                                'template',
                            ],
                            'date' => [
                                'type' => 'date',
                                'required' => true,
                                'template' => '',
                            ],
                        ]),
                        'accepts_config' => true,
                    ],
                ],
                'actions' => [
                    [
                        'name' => 'createMealPlan',
                        'label' => 'Create Meal Entry',
                        'entity' => MealPlanAutomation::class,
                        'description' => 'Create a new Meal Plan entry',
                        'config' => json_encode([
                            'meal_id' => [
                                'type' => 'id',
                                'required' => true,
                                'template',
                            ],
                            'date' => [
                                'type' => 'date',
                                'required' => true,
                                'template' => '',
                            ],
                            'meal_type_id' => [
                                'type' => 'date',
                                'required' => true,
                                'template' => '',
                            ],
                        ]),
                        'accepts_config' => true,
                    ],
                ],
                'type' => 'internal',
            ],
            'Occurrences' => [
                'name' => 'occurrence_check',
                'label' => 'Occurrence Check',
                'logo' => '/images/meal-planner.png',
                'entity' => OccurrenceAutomation::class,
                'description' => 'Occurrence check',
                'fields' => json_encode(OccurrenceAutomation::fieldConfig()),
                'actions' => [
                    [
                        'name' => 'createOccurrence',
                        'label' => 'Create Occurrence Check',
                        'entity' => OccurrenceAutomation::class,
                        'description' => 'Create an occurrence check',
                        'config' => json_encode([
                            'name' => [
                                'type' => 'id',
                                'required' => true,
                                'template',
                            ],
                            'date' => [
                                'type' => 'date',
                                'required' => true,
                                'template' => '',
                            ],
                        ]),
                        'accepts_config' => true,
                    ],
                ],
                'type' => 'internal',
            ],
        ];
    }
}
