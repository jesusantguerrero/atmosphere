<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Transaction;

class AutomationServices extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $services = [
            'Gmail' => [
                'name' => 'Gmail',
                'logo' => '/images/gmail.png',
                'entity' => 'App\Actions\Integrations\Gmail',
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
                                    'custom'
                                ]
                            ],
                            'value' => [
                                'title' => 'search text',
                                'type' => 'input',
                            ]
                        ]),
                        "accepts_config" => true,
                    ]
                ],
            ],
            'BHD' => [
                'name' => 'BHD',
                'entity' => 'App\Actions\Integrations\BHD',
                'logo' => '/images/bhd.png',
                'description' => 'BHD bank',
                'components' => [
                    [
                        'label' => 'Parse Alert',
                        'name' => 'parseAlert',
                        'entity' => 'App/Actions/Integrations/BHD',
                        'description' => 'Parse an email alert',
                        'config' => json_encode([
                            'product'=> [
                                'type' => 'string',
                                'required' => true
                            ],
                            'date' => [
                                'type' => 'date',
                                'label' => 'Date',

                            ],
                            'currency_code' => [
                                'type' => 'string',
                                'label' => 'currency_code',
                                'required' => true
                            ],
                            'amount' => [
                                'type' => 'money',
                                'label' => 'Amount',
                                'required' => true
                            ],
                            'seller' => [
                                'type' => 'string',
                                'label' => 'Seller',
                                'required' => true
                            ],
                            'status' => [
                                'type' => 'options',
                                'options' => ['aprobada']
                            ],
                            'type' => [
                                'type' => 'options',
                                'options' => ['compra'],
                                'required' => true
                            ]
                        ]),

                    ]
                ]
            ],
            'transactions' => [
                'name' => 'BHD',
                'logo' => '/images/transactions.png',
                'entity' => 'App\Actions\Integrations\Entry',
                'description' => 'BHD bank',
                'fields' => json_encode([
                    'account_id' => [
                        'type' => 'id',
                        'required' => true
                    ],
                    'date' => [
                        'type' => 'date',
                        'required' => true
                    ],
                    'currency_code' => [
                        'type' => 'string'
                    ],
                    'category_id' => [
                        'type' => 'string',
                        'required' => true
                    ],
                    'description' => [
                        'type' => 'string',
                        'required' => false
                    ],
                    'direction' => [
                        'type' => 'labels',
                        'options' => [Transaction::DIRECTION_CREDIT, Transaction::DIRECTION_DEBIT],
                    ],
                    'total' => [
                        'type' => 'money',
                        'required' => true
                    ],
                    'items' => [
                        'type' => 'array',
                        'required' => false
                    ],
                    'metaData' => [
                        'type' => 'json',
                        'required' => false
                    ]
                ]),
                'actions' => [
                    [
                        'name' => 'create',
                        'label' => 'Create transaction',
                        'entity' => 'App\Actions\Integrations\Entries',
                        'description' => 'Create a new transaction',
                        'config' => json_encode([
                            'account_id' => [
                                'type' => 'id',
                                'required' => true,
                                'template'
                            ],
                            'date' => [
                                'type' => 'date',
                                'required' => true,
                                'template' => ''
                            ],
                            'currency_code' => [
                                'type' => 'string',
                                'template' => ''
                            ],
                            'category_id' => [
                                'type' => 'string',
                                'required' => true,
                                'template' => ''
                            ],
                            'description' => [
                                'type' => 'string',
                                'required' => false,
                                'template' => ''
                            ],
                            'direction' => [
                                'type' => 'labels',
                                'options' => [Transaction::DIRECTION_CREDIT. Transaction::DIRECTION_DEBIT],
                                'template' => ''
                            ],
                            'total' => [
                                'type' => 'money',
                                'required' => true,
                                'template' => ''
                            ],
                            'items' => [
                                'type' => 'array',
                                'required' => false,
                                'template' => ''
                            ],
                            'metaData' => [
                                'type' => 'json',
                                'required' => false,
                                'template' => ''
                            ]
                        ]),
                        "accepts_config" => true,
                    ],
                ]

            ]
        ];
        foreach ($services as $serviceName => $service) {
            DB::table('automation_services')->insert([
                'name' => $serviceName,
                'label' => $service['label'] ?? $serviceName,
                'entity' => $service['entity'],
                'description' => $service['description'],
                'logo' => $service['logo'],
            ]);

            $serviceId = DB::getPdo()->lastInsertId();

            if (isset($service['triggers'])) {
                foreach ($service['triggers'] as $trigger) {
                    DB::table('automation_tasks')->insert([
                        'name' => $trigger['name'],
                        'label' => $trigger['label'],
                        'task_type' => 'trigger',
                        'entity' => $service['entity'],
                        'description' => $trigger['description'],
                        'automation_service_id' => $serviceId,
                        'config' => $trigger['config'] ?? '',
                    ]);
                }
            }

            if (isset($service['components'])) {
                foreach ($service['components'] as $component) {
                    DB::table('automation_tasks')->insert([
                        'name' => $component['name'],
                        'label' => $component['label'],
                        'task_type' => 'component',
                        'entity' => $service['entity'],
                        'description' => $component['description'],
                        'automation_service_id' => $serviceId,
                        'config' => $component['config'] ?? '',
                    ]);
                }
            }

            if (isset($service['actions'])) {
                foreach ($service['actions'] as $action) {
                    DB::table('automation_tasks')->insert([
                        'name' => $action['name'],
                        'label' => $action['label'],
                        'task_type' => 'action',
                        'entity' => $service['entity'],
                        'description' => $action['description'],
                        'automation_service_id' => $serviceId,
                        'config' => $action['config'] ?? '',
                    ]);
                }
            }
        }
    }
}
