<?php

namespace App\Providers;

use Insane\Journal\Journal;
use Illuminate\Support\ServiceProvider;
use App\Domains\Journal\Actions\AccountCreate;
use App\Domains\Journal\Actions\AccountDelete;
use App\Domains\Journal\Actions\AccountUpdate;
use App\Domains\Journal\Actions\TransactionList;
use App\Domains\Journal\Actions\TransactionShow;
use App\Domains\Journal\Actions\InvoicePaymentPay;
use App\Domains\Journal\Actions\TransactionCreate;
use App\Domains\Journal\Actions\TransactionDelete;
use App\Domains\Journal\Actions\TransactionUpdate;
use App\Domains\Journal\Actions\TransactionApprove;
use App\Domains\Journal\Actions\AccountCatalogCreate;
use App\Domains\Journal\Actions\AccountStatementList;
use App\Domains\Journal\Actions\AccountStatementShow;
use App\Domains\Journal\Actions\InvoicePaymentCreate;
use App\Domains\Journal\Actions\InvoicePaymentDelete;
use App\Domains\Journal\Actions\TransactionBulkDelete;
use App\Domains\Journal\Actions\TransactionBulkApprove;
use App\Domains\Journal\Actions\CategoryListWithBalance;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Journal\Actions\TransactionCategoriesCreate;

class JournalServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Journal::createAccountCatalogUsing(AccountCatalogCreate::class);
        Journal::createAccountDetailTypesUsing(AccountDetailTypesCreate::class);
        Journal::createTransactionCategoriesUsing(TransactionCategoriesCreate::class);

        Journal::createAccountUsing(AccountCreate::class);
        Journal::updateAccountUsing(AccountUpdate::class);
        Journal::deleteAccountUsing(AccountDelete::class);
        Journal::listAccountStatementsUsing(AccountStatementList::class);
        Journal::showAccountStatementsUsing(AccountStatementShow::class);

        Journal::listCategoryBalanceUsing(CategoryListWithBalance::class);

        Journal::payInvoiceUsing(InvoicePaymentPay::class);
        Journal::createInvoicePaymentUsing(InvoicePaymentCreate::class);
        Journal::deleteInvoicePaymentUsing(InvoicePaymentDelete::class);

        Journal::listTransactionsUsing(TransactionList::class);
        Journal::createTransactionsUsing(TransactionCreate::class);
        Journal::showTransactionsUsing(TransactionShow::class);
        Journal::updateTransactionsUsing(TransactionUpdate::class);
        Journal::deleteTransactionUsing(TransactionDelete::class);
        Journal::approveTransactionUsing(TransactionApprove::class);
        Journal::bulkApproveTransactionsUsing(TransactionBulkApprove::class);
        Journal::bulkDeleteTransactionsUsing(TransactionBulkDelete::class);
    }
}
