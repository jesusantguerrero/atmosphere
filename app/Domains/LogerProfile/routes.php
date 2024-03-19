<?php

use Illuminate\Support\Facades\Route;
use App\Domains\LogerProfile\Http\Controllers\LogerProfileController;
use App\Domains\LogerProfile\Http\Controllers\LogerProfileEntityController;

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    // Route::resource('/loger-profiles', [LogerProfileController::class, 'index'])->name('profiles.index');
    Route::resource('/loger-profiles', LogerProfileController::class);
    Route::resource('/loger-profiles/{profileId}/entities', LogerProfileEntityController::class);
    Route::get('/loger-profiles/{profileId}/transactions', [LogerProfileController::class, 'transactions']);
    Route::get('/relationships/overview', [LogerProfileController::class, 'overview'])->name('relationships-overview');
    Route::get('/relationships/{profileName}', [LogerProfileController::class, 'relationships'])->name("relationships.index");
});
