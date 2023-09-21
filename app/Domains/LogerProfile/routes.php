<?php

use App\Domains\LogerProfile\Http\Controllers\LogerProfileController;
use App\Domains\LogerProfile\Http\Controllers\LogerProfileEntityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    // Route::resource('/loger-profiles', [LogerProfileController::class, 'index'])->name('profiles.index');
    Route::resource('/loger-profiles', LogerProfileController::class);
    Route::resource('/loger-profiles/{profileId}/entities', LogerProfileEntityController::class);
});
