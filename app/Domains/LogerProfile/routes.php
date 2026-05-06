<?php

use App\Domains\LogerProfile\Http\Controllers\LogerProfileController;
use App\Domains\LogerProfile\Http\Controllers\LogerProfileEntityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified', 'loger.concerns:profiles'])->group(function () {
    // Route::resource('/loger-profiles', [LogerProfileController::class, 'index'])->name('profiles.index');
    Route::resource('/loger-profiles', LogerProfileController::class);
    Route::resource('/loger-profiles/{profileId}/entities', LogerProfileEntityController::class);
    Route::get('/loger-profiles/{profileId}/transactions', [LogerProfileController::class, 'transactions']);
    Route::get('/relationships/overview', [LogerProfileController::class, 'overview'])->name('relationships-overview');
    Route::get('/relationships/{profileName}', [LogerProfileController::class, 'relationships'])->name('profile.relationships');
});

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::get('/relationships-mock', fn () => inertia('Relationships/CoupleSupportMock'))
        ->name('relationships.mock');
});
