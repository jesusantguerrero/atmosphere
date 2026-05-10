<?php

use App\Domains\Transaction\Http\Controllers\ReconciliationController;
use App\Http\Controllers\Api\CreditCardSummaryController;
use App\Http\Controllers\Finance\FinanceAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::get('/credit-card-summary', CreditCardSummaryController::class)->name('credit-card-summary');

    Route::post('/finance/accounts/{account}/automation-services/{automationService}/link', [FinanceAccountController::class, 'linkAccount']);
    Route::post('/finance/accounts/{account}/link-bank', [FinanceAccountController::class, 'linkAccountToBank'])->name('accounts.link-bank');
    Route::post('/finance/accounts/{account}/sync-emails', [FinanceAccountController::class, 'syncEmails'])->name('accounts.sync-emails');
    Route::put('/finance/accounts/{account}/close', [FinanceAccountController::class, 'closeAccount'])->name('accounts.close');

    Route::get('/finance/reconciliation/accounts/{account}', [ReconciliationController::class, 'create']);
    Route::post('/finance/reconciliation/accounts/{account}', [ReconciliationController::class, 'store']);
    Route::get('/finance/accounts/{account}/reconciliations', [ReconciliationController::class, 'accountReconciliations']);

    Route::get('/finance/reconciliation/{reconciliation}', [ReconciliationController::class, 'show']);
    Route::put('/finance/reconciliation/{reconciliation}/save-adjustment', [ReconciliationController::class, 'adjustment']);
    Route::put('/finance/reconciliation/{reconciliation}', [ReconciliationController::class, 'update']);
    Route::put('/finance/reconciliation/{reconciliation}/sync-transactions', [ReconciliationController::class, 'syncTransactions']);
    Route::delete('/finance/reconciliation/{reconciliation}', [ReconciliationController::class, 'delete']);

    Route::put('/finance/reconciliation/{reconciliation}/reconciliation-entries/{reconciliationEntry}/check', [ReconciliationController::class, 'checkReconciliationEntry']);
});
