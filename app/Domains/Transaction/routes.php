<?php

use App\Domains\Transaction\Http\Controllers\ReconciliationController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::get('/finance/reconciliation/accounts/{account}', [ReconciliationController::class, 'create']);
    Route::post('/finance/reconciliation/accounts/{account}', [ReconciliationController::class, 'store']);
    Route::get('/finance/reconciliation/{reconciliation}', [ReconciliationController::class, 'show']);
});


