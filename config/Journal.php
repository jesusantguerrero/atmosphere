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
    "accounts_categories" => [
        [
            "resource_type" => "categories",
            "display_id" => "assets",
            "name" => "Assets",
            "Description" => "",
            "depth" => 0,
            "childs" => [
                [
                    "resource_type" => "categories",
                    "display_id" => "cash_and_bank",
                    "name" => "Cash and bank",
                    "Description" => "Use this to track the balance of cash that is immediately available for use. Examples of this are bank accounts, cash boxes in a register, money boxes, or electronic accounts such as PayPa",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "money_in_transit",
                    "name" => "Money in Transit",
                    "Description" => "Use this to track the balance of money that is expected to deposited or withdrawn into or from a Cash and Bank account at a future date, usually within days. Examples of this are credit card sales that have been processed but have not yet been deposited into your bank, or checks (written or received) that have not been deposited into or withdrawn from your bank account yet.",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "expected_payments_customers",
                    "name" => "Expected Payments from Customers",
                    "Description" => "Use this to track the balance of what customers owe you after you have made a sale. Invoices in Journal are already tracked in the Accounts Receivable category.",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "inventory",
                    "name" => "Inventory",
                    "Description" => "",
                    "depth" => 1,
                ],
            ]
        ],
        [
            "resource_type" => "categories",
            "name" => "Liabilities & Credit Cards",
            "display_id" => "liabilities",
            "Description" => "",
            "depth" => 0,
            "childs" => [
                [
                    "resource_type" => "categories",
                    "display_id" => "credit_card",
                    "name" => "Credit Card",
                    "Description" => "Use this to track purchases made using a credit card. Create an account for each credit card you use in your business. Purchases using your credit card, and payments to your credit card, should be recorded in the relevant credit card category.",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "loan_line_credit",
                    "name" => "Loan and Line of Credit",
                    "Description" => "Use this to track the balance of outstanding loans or withdrawals you've made using a line of credit. The cash you receive as a result of a loan or line of credit is deposited into a Cash and Bank category.",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "expected_payments_vendors",
                    "name" => "Expected Payments to Vendors",
                    "Description" => "Use this to track the balance of what you owe vendors (i.e. suppliers, online subscriptions providers) after you accepted their service or receive items for which you have not yet paid. Journal in Wave are already tracked in the Accounts Payable category",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "sales_taxes",
                    "name" => "Sales Taxes",
                    "Description" => "Use this to track the sales taxes you have charged to customers during a sale, and sales tax amounts you have remitted to the government. The balance of this category indicates how much you have to remit to the government. This category can also be used to track sales taxes you been charged on purchases, so that you can reduce how much sales taxes you have to remit to the government. If you create a sales tax in Wave, a category here is created for you automatically.",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "due_payroll",
                    "name" => "Due for Payroll",
                    "Description" => "Use this to track all amounts owed that relate to having employees and running a payroll. This includes salaries, wages, and employee reimbursements, but also all payroll taxes that must be paid to government agencies and other collectors (ie; insurance agencies and health savings providers).",

                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "due_to_business",
                    "name" => "Due to You and Others Business Owners",
                    "Description" => "Use this to track the balance of what you (or your partners) have personally loaned to the business, but expect to be paid back for. The same category can also be used to track loans the business has given you (or your partners), in which case the balance would be less than zero (negative).",

                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "customer_prepayments",
                    "name" => "Customer Prepayments and Customer Credits",
                    "Description" => "",
                    "depth" => 1,
                ],
            ]
        ],
        [
            "resource_type" => "categories",
            "name" => "Incomes",
            "display_id" => "incomes",
            "Description" => "",
            "depth" => 0,
            "childs" => [
                [
                    "resource_type" => "categories",
                    "display_id" => "income",
                    "name" => "Income",
                    "Description" => "Use this to track all your sales to customers, whether your customer has made a payment or not. These are the categories used when you create an Invoice in Wave. Any sales taxes charged to customers will not be tracked using a Sales category, but will be tracked using a Sales Taxes on Sales or Purchases category.",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "discount",
                    "name" => "Discounts",
                    "Description" => "",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "other_income",
                    "name" => "Other Income",
                    "Description" => "Use this to track all other income that is outside of your regular business operations of selling to your customers. For example, if your main business is as a photographer, but you rented your camera to a friend as a one-off shoot, that could be other income.",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "uncategorized_incomes",
                    "name" => "Uncategorized Income",
                    "Description" => "This account is used as the default category for new deposit transactions.",
                    "depth" => 1,
                ],
            ]
        ],
        [
            "resource_type" => "categories",
            "name" => "Expense",
            "display_id" => "expenses",
            "Description" => "",
            "depth" => 0,
            "childs" => [
                [
                    "resource_type" => "categories",
                    "display_id" => "operating_expense",
                    "name" => "Operating Expense",
                    "Description" => "",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "cost_goods_sold",
                    "name" => "Cost of Goods Sold",
                    "Description" => "",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "payment_processing_fee",
                    "name" => "Payment Processing Fee",
                    "Description" => "",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "payroll_expense",
                    "name" => "Payroll Expense",
                    "Description" => "",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "uncategorized_expense",
                    "name" => "Uncategorized Expense",
                    "Description" => "",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "loss_foreign_exchange",
                    "name" => "Loss on Foreign Exchange",
                    "Description" => "",
                    "depth" => 1,
                ],
            ]
        ],
        [
            "resource_type" => "categories",
            "name" => "Equity",
            "display_id" => "equity",
            "description" => "",
            "depth" => 0,
            "childs" => [
                [
                    "resource_type" => "categories",
                    "display_id" => "business_owner_contribution",
                    "name" => "Business Owner Contribution and Drawing",
                    "Description" => "Use this to track money you or others have invested into the business. For example, when you first start a business you usually invest start-up money into it",
                    "depth" => 1
                ],
                [
                    "resource_type" => "categories",
                    "display_id" => "retained_earnings",
                    "name" => "Retained Earnings: Profit",
                    "Description" => "Use this to track money that you have taken out of the business.",
                    "depth" => 1
                ]
            ]
        ]
    ],
    "accounts_catalog" => [
        // Assets Accounts
        [
            "category_id" => "cash_and_bank",
            "display_id" => "cash_on_hand",
            "name" => "Cash on Hand",
            "index" => 0,
            "type" => 1,
            "balance_type" => "DEBIT"
        ],
        [
            "category_id" => "cash_and_bank",
            "display_id" => "daily_box",
            "name" => "Daily Box",
            "index" => 1,
            "balance_type" => "DEBIT"
        ],
        [
            "category_id" => "expected_payments_customers",
            "display_id" => "accounts_receivable",
            "name" => "Accounts Receivable",
            "index" => 1,
            "balance_type" => "CREDIT"
        ],
        // Liabilities & Credit Cards
        [
            "category_id" => "expected_payments_vendors",
            "display_id" => "accounts_payable",
            "name" => "Accounts Payable",
            "index" => 1,
            "balance_type" => "CREDIT"
        ],

        //
        [
            "category_id" => "inventory",
            "display_id" => "products",
            "name" => "Products",
            "index" => 1,
            "balance_type" => "DEBIT"
        ],
        [
            "category_id" => "equipment",
            "display_id" => "machines",
            "name" => "Machines",
            "index" => 1,
            "balance_type" => "DEBIT"
        ],
        // Income Accounts
        [
            "category_id" => "income",
            "display_id" => "consulting_income",
            "name" => "Consulting Income",
            "index" => 1,
            "balance_type" => "DEBIT"
        ],
        [
            "category_id" => "income",
            "display_id" => "product_sales",
            "name" => "Product Sales",
            "index" => 2,
            "balance_type" => "DEBIT"
        ],
        [
            "category_id" => "income",
            "display_id" => "sales",
            "name" => "Sales",
            "index" => 3,
            "balance_type" => "DEBIT"
        ],
        [
            "category_id" => "uncategorized_incomes",
            "display_id" => "uncategorized_income",
            "name" => "Uncategorized Income",
            "index" => 3,
            "balance_type" => "DEBIT"
        ],

        // Expense Accounts
        [
            "category_id" => "operating_expense",
            "display_id" => "accounting_fees",
            "name" => "Accounting Fees",
            "index" => 1,
            "balance_type" => "CREDIT"
        ],
        [
            "category_id" => "operating_expense",
            "display_id" => "advertising",
            "name" => "Advertising & Promotion",
            "index" => 2,
            "balance_type" => "CREDIT"
        ],
        [
            "category_id" => "operating_expense",
            "display_id" => "bank_services_charges",
            "name" => "Bank Service Charges",
            "index" => 3,
            "balance_type" => "CREDIT"
        ],
        [
            "category_id" => "operating_expense",
            "display_id" => "computer_hardware",
            "name" => "Computer – Hardware",
            "index" => 4,
            "balance_type" => "CREDIT"
        ],
        [
            "category_id" => "operating_expense",
            "display_id" => "computer_hosting",
            "name" => "Computer – Hosting",
            "index" => 5,
            "balance_type" => "CREDIT"
        ],
        [
            "category_id" => "operating_expense",
            "display_id" => "computer_internet",
            "name" => "Computer – Internet",
            "index" => 6,
            "balance_type" => "CREDIT"
        ],
        [
            "category_id" => "operating_expense",
            "display_id" => "computer_hosting",
            "name" => "Computer – Software",
            "index" => 7,
            "balance_type" => "CREDIT"
        ],
        [
            "category_id" => "operating_expense",
            "display_id" => "dues_subscriptions",
            "name" => "Dues & Subscriptions",
            "index" => 8,
            "balance_type" => "CREDIT"
        ],
        [
            "category_id" => "operating_expense",
            "display_id" => "office_supplies",
            "name" => "Office Supplies",
            "index" => 9,
            "balance_type" => "CREDIT"
        ],
        [
            "category_id" => "operating_expense",
            "display_id" => "professional_fees",
            "name" => "Professional Fees",
            "index" => 10,
            "balance_type" => "CREDIT"
        ],
        [
            "category_id" => "operating_expense",
            "display_id" => "rent_expense",
            "name" => "Rent Expense",
            "index" => 11,
            "balance_type" => "CREDIT"
        ],

        // Capital accounts
        [
            "category_id" => "business_owner_contribution",
            "display_id" => "owner_investment",
            "name" => "Owner Investment / Drawings",
            "index" => 1,
            "balance_type" => "CREDIT",
            "type" => -1
        ],
        [
            "category_id" => "retained_earnings",
            "display_id" => "owner_equity",
            "name" => "Owner's Equity",
            "index" => 1,
            "balance_type" => "DEBIT",
            "type" => 1
        ],
    ],
    "categories" => [
        [
            "resource_type" => "transactions",
            "display_id" => "food_and_drinks",
            "name" => "Food & Drinks",
            "Description" => "Food and drinks",
            "depth" => 0,
            "childs" => [
                [
                    "resource_type" => "transactions",
                    "display_id" => "food_general",
                    "name" => "General - Food & Drinks",
                    "Description" => "General food and drinks",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "bar_cafe",
                    "name" => "Bar, Cafe",
                    "Description" => "General food and drinks",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "groceries",
                    "name" => "Groceries",
                    "Description" => "Groceries",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "restaurant",
                    "name" => "Restaurant, Fast Food",
                    "Description" => "Food out",
                    "depth" => 1,
                ],
            ]
        ],
        [
            "resource_type" => "transactions",
            "display_id" => "shopping",
            "name" => "Shopping",
            "Description" => "Shopping",
            "depth" => 0,
            "childs" => [
                [
                    "resource_type" => "transactions",
                    "display_id" => "shopping_general",
                    "name" => "General - Shopping",
                    "Description" => "General shopping",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "clothing",
                    "name" => "Clothing & shoes",
                    "Description" => "Clothing",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "home_appliances",
                    "name" => "Home Appliances",
                    "Description" => "Home appliances",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "electronics",
                    "name" => "Electronics",
                    "Description" => "Electronics",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "health_beauty",
                    "name" => "Health & Beauty",
                    "Description" => "Health and beauty",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "home_decor",
                    "name" => "Home Decor",
                    "Description" => "Home decor",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "sports_outdoors",
                    "name" => "Sports & Outdoors",
                    "Description" => "Sports and outdoors",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "free_time",
                    "name" => "free_time",
                    "Description" => "Free time, joy",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "others",
                    "name" => "Others",
                    "Description" => "Others",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "kids",
                    "name" => "Kids",
                    "Description" => "Kids",
                    "depth" => 1,
                ],
            ]
        ],
        [
            "resource_type" => "transactions",
            "display_id" => "housing",
            "name" => "Housing",
            "Description" => "Housing",
            "depth" => 0,
            "childs" => [
                [
                    "resource_type" => "transactions",
                    "display_id" => "housing_general",
                    "name" => "General - Housing",
                    "Description" => "General housing",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "mortgage",
                    "name" => "Mortgage",
                    "Description" => "Mortgage",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "rent",
                    "name" => "Rent",
                    "Description" => "Rent",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "utilities",
                    "name" => "Utilities",
                    "Description" => "Utilities",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "home_services",
                    "name" => "Home Services",
                    "Description" => "Home services",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "home_insurance",
                    "name" => "Home Insurance",
                    "Description" => "Home insurance",
                    "depth" => 1,
                ]
            ]
        ],
        [
            "resource_type" => "transactions",
            "display_id" => "transportation",
            "name" => "Transportation",
            "Description" => "Transportation",
            "depth" => 0,
        ],
        [
            "resource_type" => "transactions",
            "display_id" => "vehicle",
            "name" => "Vehicle",
            "Description" => "Vehicle",
            "depth" => 0,
        ],
        [
            "resource_type" => "transactions",
            "display_id" => "entertainment",
            "name" => "Entertainment",
            "Description" => "Entertainment",
            "depth" => 0,
        ],
        [
            "resource_type" => "transactions",
            "display_id" => "communication",
            "name" => "Communication & PC",
            "Description" => "Communication and PC",
            "depth" => 0,
        ],
        [
            "resource_type" => "transactions",
            "display_id" => "financial_expenses",
            "name" => "Financial expenses",
            "Description" => "Financial expenses",
            "depth" => 0,
            "childs" => [
                [
                    "resource_type" => "transactions",
                    "display_id" => "taxes",
                    "name" => "Taxes",
                    "Description" => "Taxes",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "fees",
                    "name" => "Fees",
                    "Description" => "Fees",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "loans",
                    "name" => "Loans",
                    "Description" => "Loans",
                    "depth" => 1,
                ]
            ]

        ],
        [
            "resource_type" => "transactions",
            "display_id" => "investments",
            "name" => "Investments",
            "Description" => "Investments",
            "depth" => 0,
            "childs" => [
                [
                    "resource_type" => "transactions",
                    "display_id" => "investments_general",
                    "name" => "General - Investments",
                    "Description" => "General investments",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "savings",
                    "name" => "Savings",
                    "Description" => "Savings",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "financial_investments",
                    "name" => "Financial Investments",
                    "Description" => "Financial investments",
                    "depth" => 1,
                ],
                [
                    "resource_type" => "transactions",
                    "display_id" => "cryptocurrencies",
                    "name" => "Cryptocurrencies",
                    "Description" => "Cryptocurrencies",
                    "depth" => 1,
                ]
            ]
        ],
        [
            "resource_type" => "transactions",
            "display_id" => "others",
            "name" => "Others",
            "Description" => "Others",
            "depth" => 0,
        ]
    ],
    "features" => [
        // Features::transactionCategories(),
    ]
];
