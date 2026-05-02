<?php

return [
    'accounts_inertia_path' => 'Journal/Accounts',
    'statements_inertia_path' => 'Journal/Statements',
    'transactions_inertia_path' => 'Journal/Transactions',
    'products_inertia_path' => 'Journal/Catalog/Products',
    'categories_inertia_path' => 'Journal/Catalog/Categories',
    'invoices_inertia_path' => 'Journal/Invoices',
    'payments_inertia_path' => 'Journal/Payments',
    'accounts_categories' => [],
    'accounts_catalog' => [],
    'categories' => [
        [
            'resource_type' => 'transactions',
            'display_id' => 'inflow',
            'name' => 'Inflow',
            'description' => 'To be budgeted',
            'depth' => 1,
            'type' => 1,
            'childs' => [
                [
                    'resource_type' => 'transactions',
                    'display_id' => 'ready_to_assign',
                    'type' => 1,
                    'name' => 'Ready to Assign',
                    'Description' => 'Money expecting assignment',
                    'depth' => 1,
                ],
            ],
        ],
        [
            'resource_type' => 'transactions',
            'display_id' => 'savings',
            'name' => 'Savings',
            'description' => 'Money set aside for future goals',
            'depth' => 1,
            'type' => 1,
            'childs' => [
                [
                    'resource_type' => 'transactions',
                    'display_id' => 'savings_general',
                    'type' => 1,
                    'name' => 'Ahorro',
                    'description' => 'General savings allocation',
                    'depth' => 1,
                ],
            ],
        ],
        [
            'resource_type' => 'transactions',
            'display_id' => 'personal',
            'name' => 'Personal',
            'description' => 'Discretionary personal spending',
            'depth' => 1,
            'type' => 1,
            'childs' => [
                [
                    'resource_type' => 'transactions',
                    'display_id' => 'personal_spending',
                    'type' => 1,
                    'name' => 'Gasto Personal',
                    'description' => 'Free-spend money for the period',
                    'depth' => 1,
                ],
            ],
        ],
    ],
];
