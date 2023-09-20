<?php

use App\Domains\Automation\Http\Controllers\AutomationController;
use App\Domains\Automation\Http\Controllers\AutomationRecipeController;
use App\Domains\Automation\Http\Controllers\AutomationServiceController;
use Illuminate\Support\Facades\Route;

/**************************************************************************************
 *                                  API Section
 ***************************************************************************************/

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->prefix('/api')->name('api.')->group(function () {
    Route::controller(AutomationController::class)->group(function () {
        Route::apiResource('automation', AutomationController::class);
        Route::post('/automation/{id}/run', 'run');
        Route::post('/automation/run-all', 'runAll');
    });

    Route::apiResource('/automation-services', AutomationServiceController::class);
    Route::apiResource('/automation-recipes', AutomationRecipeController::class);
});
