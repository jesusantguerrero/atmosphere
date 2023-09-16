<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\AccountStatementLists;
use Insane\Journal\Models\Core\Account;

class AccountStatementList implements AccountStatementLists
{
   
    public function list(User $user, string $categoryName): array
    {
        $this->validate($user);
        return [
            "financial" => [
                "label" => "Financial Statements",
                "description" => "Get a clear picture of how your business is doing. Use these core statements to better understand your financial health.",
                "reports" => [
                    "balance-sheet" => [
                        "label" => "Balance Sheet",
                        "description" => "This statement shows the current balance of your accounts. It includes all of your accounts and their balances.",
                        "url" => "/statements/balance-sheet",
                    ],
                    "income-statement" => [
                        "label" => "Income Statement",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/statements/income-statement",
                    ],
                    "cash-flow" => [
                        "label" => "Cash Flow",
                        "description" => "This statement shows the cash flow of your business. It includes all of your accounts and their balances.",
                        "url" => "/statements/cash-flow",
                    ],
                ],
            ],
            "taxes" => [
                "label" => "Taxes",
                "description" => "Get a clear picture of how your business is doing. Use these core statements to better understand your financial health.",
                "reports" => [
                    "tax-return" => [
                        "label" => "Tax Return",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/reports/tax",
                    ],
                ],
            ],
            "customers" => [
                "label" => "Customers",
                "description" => "Get a clear picture of how your business is doing. Use these core statements to better understand your financial health.",
                "reports" => [
                    "income-customers" => [
                        "label" => "Customer List",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/customer-list",
                    ],
                    "aged-receivables" => [
                        "label" => "Aged Receivables",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/aged-receivables",
                    ],
                ],
            ],
            "vendors" => [
                "label" => "Vendors",
                "description" => "Get a clear picture of how your business is doing. Use these core statements to better understand your financial health.",
                "reports" => [
                    "purchases-vendors" => [
                        "label" => "Purchases by Vendor",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/vendor-list",
                    ],
                    "aged-payables" => [
                        "label" => "Aged Payables",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/aged-payables",
                    ],
                ],
            ],
            "detailed" => [
                "label" => "Detailed Reporting",
                "description" => "Get a clear picture of how your business is doing. Use these core statements to better understand your financial health.",
                "reports" => [
                    "balances" => [
                        "label" => "Account Balances",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/statements/account-balance",
                    ],
                    "trial_balance" => [
                        "label" => "Detailed Expenses",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/detailed-expenses",
                    ],
                    "transactions" => [
                        "label" => "Account Transactions",
                        "description" => "This statement shows the income and expenses of your business. It includes all of your accounts and their balances.",
                        "url" => "/accounts/statements/detailed-transactions",
                    ]
                ],
            ]
        ];
    }

    public function validate(mixed $user)
    {
        Gate::forUser($user)->authorize('show', Account::class);   
    }
}
