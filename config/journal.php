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

    /*
    |--------------------------------------------------------------------------
    | Default budget category seed
    |--------------------------------------------------------------------------
    |
    | This list is seeded once per team via TransactionCategoriesCreate on the
    | TeamCreated event. A newcomer's Budget page renders directly from this
    | template, so it needs to be usable-as-shipped rather than force the user
    | to invent a category taxonomy before they can budget anything.
    |
    | Structure follows the YNAB "priorities" grouping (Immediate Obligations
    | → True Expenses → Quality of Life → Just for Fun) which people generally
    | recognize and can adapt. Everything under `childs` is user-renamable.
    |
    | RESERVED — do not rename or change display_id (referenced from code):
    |   - group 'Inflow'                (BudgetReservedNames::INFLOW)
    |   - child name 'Ready to Assign'  (BudgetReservedNames::READY_TO_ASSIGN,
    |                                    matched by literal `name` string in
    |                                    many BudgetMonth queries)
    |   - display_id 'ready_to_assign'  (BudgetExport, BudgetCategoryService)
    |   - display_id 'savings_general'  (CreateTeamSettings role mapping)
    |   - display_id 'personal_spending'(CreateTeamSettings role mapping)
    |
    | Everything else is a suggested starter category. Localisation and a
    | template picker are separate follow-ups (see .planning/ if present).
    */
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
                    'description' => 'Money expecting assignment',
                    'depth' => 1,
                ],
            ],
        ],

        [
            'resource_type' => 'transactions',
            'display_id' => 'immediate_obligations',
            'name' => 'Immediate Obligations',
            'description' => 'Bills with severe consequences if unpaid this month',
            'depth' => 1,
            'type' => 1,
            'childs' => [
                ['resource_type' => 'transactions', 'display_id' => 'rent_mortgage', 'type' => 1, 'name' => 'Rent / Mortgage', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'electricity', 'type' => 1, 'name' => 'Electricity', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'water', 'type' => 1, 'name' => 'Water', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'internet', 'type' => 1, 'name' => 'Internet', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'cellphone', 'type' => 1, 'name' => 'Cellphone', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'groceries', 'type' => 1, 'name' => 'Groceries', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'transportation', 'type' => 1, 'name' => 'Transportation', 'depth' => 1],
            ],
        ],

        [
            'resource_type' => 'transactions',
            'display_id' => 'true_expenses',
            'name' => 'True Expenses',
            'description' => 'Predictable but irregular — save monthly, spend later',
            'depth' => 1,
            'type' => 1,
            'childs' => [
                ['resource_type' => 'transactions', 'display_id' => 'auto_maintenance', 'type' => 1, 'name' => 'Auto Maintenance', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'home_maintenance', 'type' => 1, 'name' => 'Home Maintenance', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'medical', 'type' => 1, 'name' => 'Medical', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'insurance', 'type' => 1, 'name' => 'Insurance', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'clothing', 'type' => 1, 'name' => 'Clothing', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'gifts', 'type' => 1, 'name' => 'Gifts', 'depth' => 1],
            ],
        ],

        [
            'resource_type' => 'transactions',
            'display_id' => 'quality_of_life',
            'name' => 'Quality of Life Goals',
            'description' => 'Self-investment and planned experiences',
            'depth' => 1,
            'type' => 1,
            'childs' => [
                ['resource_type' => 'transactions', 'display_id' => 'education', 'type' => 1, 'name' => 'Education', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'vacation', 'type' => 1, 'name' => 'Vacation', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'fitness', 'type' => 1, 'name' => 'Fitness', 'depth' => 1],
            ],
        ],

        [
            'resource_type' => 'transactions',
            'display_id' => 'just_for_fun',
            'name' => 'Just for Fun',
            'description' => 'Entertainment and hobbies',
            'depth' => 1,
            'type' => 1,
            'childs' => [
                ['resource_type' => 'transactions', 'display_id' => 'dining_out', 'type' => 1, 'name' => 'Dining Out', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'entertainment', 'type' => 1, 'name' => 'Entertainment', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'subscriptions', 'type' => 1, 'name' => 'Subscriptions', 'depth' => 1],
                ['resource_type' => 'transactions', 'display_id' => 'hobbies', 'type' => 1, 'name' => 'Hobbies', 'depth' => 1],
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
                ['resource_type' => 'transactions', 'display_id' => 'emergency_fund', 'type' => 1, 'name' => 'Emergency Fund', 'depth' => 1],
                [
                    'resource_type' => 'transactions',
                    'display_id' => 'savings_general',
                    'type' => 1,
                    'name' => 'General Savings',
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
                    'name' => 'Personal Spending',
                    'description' => 'Free-spend money for the period',
                    'depth' => 1,
                ],
            ],
        ],
    ],
];
