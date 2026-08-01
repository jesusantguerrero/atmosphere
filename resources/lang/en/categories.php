<?php

/*
|--------------------------------------------------------------------------
| Default budget category names
|--------------------------------------------------------------------------
|
| Keyed by `display_id` from config/journal.php. Used by
| TransactionCategoriesCreate at team-creation time to seed the Budget page
| in the current app locale.
|
| RESERVED — do not add translations for these display_ids; their names are
| matched literally in code (see BudgetReservedNames): inflow, ready_to_assign.
|
*/
return [
    // Group names (top-level)
    'immediate_obligations' => 'Immediate Obligations',
    'true_expenses' => 'True Expenses',
    'quality_of_life' => 'Quality of Life Goals',
    'just_for_fun' => 'Just for Fun',
    'savings' => 'Savings',
    'personal' => 'Personal',

    // Group descriptions (used at seed time only; not shown in table rows)
    'immediate_obligations_desc' => 'Bills with severe consequences if unpaid this month',
    'true_expenses_desc' => 'Predictable but irregular — save monthly, spend later',
    'quality_of_life_desc' => 'Self-investment and planned experiences',
    'just_for_fun_desc' => 'Entertainment and hobbies',
    'savings_desc' => 'Money set aside for future goals',
    'personal_desc' => 'Discretionary personal spending',

    // Immediate Obligations
    'rent_mortgage' => 'Rent / Mortgage',
    'electricity' => 'Electricity',
    'water' => 'Water',
    'internet' => 'Internet',
    'cellphone' => 'Cellphone',
    'groceries' => 'Groceries',
    'transportation' => 'Transportation',

    // True Expenses
    'auto_maintenance' => 'Auto Maintenance',
    'home_maintenance' => 'Home Maintenance',
    'medical' => 'Medical',
    'insurance' => 'Insurance',
    'clothing' => 'Clothing',
    'gifts' => 'Gifts',

    // Quality of Life
    'education' => 'Education',
    'vacation' => 'Vacation',
    'fitness' => 'Fitness',

    // Just for Fun
    'dining_out' => 'Dining Out',
    'entertainment' => 'Entertainment',
    'subscriptions' => 'Subscriptions',
    'hobbies' => 'Hobbies',

    // Savings
    'emergency_fund' => 'Emergency Fund',
    'savings_general' => 'General Savings',

    // Personal
    'personal_spending' => 'Personal Spending',
];
