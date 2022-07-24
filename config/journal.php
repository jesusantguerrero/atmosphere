<?php
use Insane\Journal\Features;

return [
    "accounts_inertia_path" => "Journal/Accounts",
    "statements_inertia_path" => "Journal/Statements",
    "transactions_inertia_path" => "Journal/Transactions",
    "products_inertia_path" => "Journal/Catalog/Products",
    "categories_inertia_path" => "Journal/Catalog/Categories",
    "invoices_inertia_path" => "Journal/Invoices",
    "payments_inertia_path" => "Journal/Payments",
    "accounts_categories" => [],
    "accounts_catalog" => [],
    "categories" => [
        [
            "resource_type" => "transactions",
            "display_id" => "inflow",
            "name" => "Inflow",
            "description" => "To be budgeted",
            "depth" => 1,
            "childs" => [
                [
                    "resource_type" => "transactions",
                    "display_id" => "ready_to_assign",
                    "name" => "Ready to Assign",
                    "Description" => "Money expecting assignment",
                    "depth" => 1,
                ]
            ]
        ]
    ],
];
