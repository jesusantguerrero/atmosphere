<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddPlannerItemsTool;
use App\Mcp\Tools\CreatePlannedTransactionTool;
use App\Mcp\Tools\CreateTransactionTool;
use App\Mcp\Tools\GetMonthBriefTool;
use App\Mcp\Tools\GetSpendingByCategoryTool;
use App\Mcp\Tools\GetUpcomingPaymentsTool;
use App\Mcp\Tools\ImportBankStatementTool;
use App\Mcp\Tools\ListAccountsAndCategoriesTool;
use App\Mcp\Tools\ListTransactionsTool;
use App\Mcp\Tools\MarkPaymentPaidTool;
use App\Mcp\Tools\UpdateTransactionTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Loger')]
#[Version('1.0.0')]
#[Instructions('Personal finance of the token owner\'s current household in Loger. Amounts are in the household primary currency (usually DOP, written RD$) unless an account says otherwise; months are YYYY-MM and dates YYYY-MM-DD in the household time zone. Prefer get_month_brief for "¿cómo voy?" questions and get_spending_by_category for a single category. Before create_transaction or mark_payment_paid, resolve names to ids with list_accounts_and_categories or get_upcoming_payments, and confirm the amount, account and category with the user. Before update_transaction, find the transaction with list_transactions, show the user how it is and how it will be, and wait for their confirmation. Before create_planned_transaction, confirm the description, amount, account, first date and recurrence (e.g. every 4 weeks) with the user, then show the next dates it returns. Transactions cannot be deleted from here. For add_planner_items and import_bank_statement, read the shared document yourself, show the extracted list and confirm it (and which account) before calling.')]
class LogerServer extends Server
{
    protected array $tools = [
        GetMonthBriefTool::class,
        GetSpendingByCategoryTool::class,
        ListTransactionsTool::class,
        GetUpcomingPaymentsTool::class,
        ListAccountsAndCategoriesTool::class,
        CreateTransactionTool::class,
        UpdateTransactionTool::class,
        CreatePlannedTransactionTool::class,
        MarkPaymentPaidTool::class,
        AddPlannerItemsTool::class,
        ImportBankStatementTool::class,
    ];
}
