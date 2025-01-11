<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Budget\Http\Controllers\BudgetFundController;
use App\Domains\Budget\Http\Controllers\BudgetMonthController;
use App\Domains\Budget\Http\Controllers\BudgetTargetController;
use App\Domains\Budget\Http\Controllers\BudgetCategoryController;
use App\Domains\Budget\Http\Controllers\BudgetMatchAccountController;

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::resource('/budgets', BudgetCategoryController::class);
    Route::get('/budget-alerts', [BudgetCategoryController::class, 'budgetAlerts'])->name('budget-alerts');
    Route::controller(BudgetTargetController::class)->group(function () {
        Route::post('/budgets/{category}/targets/', 'store')->name('budget.target.store');
        Route::put('/budgets/{category}/targets/{budgetTarget}', 'update')->name('budget.target.update');
        Route::post('/budgets/{category}/targets/{budgetTarget}', 'complete')->name('budget-target.complete');
    });
    Route::controller(BudgetMonthController::class)->group(function () {
        Route::post('/budgets/{category}/months/{month}', 'assign')->name('budget.assignment');
        Route::put('/budgets/{category}/months/{month}', 'updateActivity')->name('budget.update-activity');
        Route::post('/budgets-import', 'import')->name('budget.import');
        Route::get('/budgets-export', 'export')->name('budget.export');
    });

    Route::controller(BudgetMatchAccountController::class)->group(function () {
        Route::get('/budgets/{category}/budget-match-accounts/{id?}', 'index');
        Route::get('/budgets/{category}/budget-match-accounts/{budgetMatchAccount}', 'destroy');
        Route::post('/budgets/{category}/budget-match-accounts', 'store')->name('budget.match-account.store');
        Route::put('/budgets/{category}/budget-match-accounts/{budgetMatchAccount}', 'update');
        Route::delete('/budgets/{category}/budget-match-accounts/{budgetMatchAccount}', 'destroy');
    });

    Route::resource('/budget-funds', BudgetFundController::class);

});
