<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Budget\Http\Controllers\BudgetMonthController;
use App\Domains\Budget\Http\Controllers\BudgetTargetController;
use App\Domains\Budget\Http\Controllers\BudgetCategoryController;

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::resource('/budgets', BudgetCategoryController::class);
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
});
