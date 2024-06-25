<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Finance\FinanceAccountController;
use App\Domains\Transaction\Http\Controllers\ReconciliationController;

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::post('/finance/accounts/{account}/automation-services/{automationService}/link', [FinanceAccountController::class, 'linkAccount']);

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
