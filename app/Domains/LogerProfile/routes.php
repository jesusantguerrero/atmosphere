<?php

use App\Domains\LogerProfile\Http\Controllers\LogerProfileController;
use App\Domains\LogerProfile\Http\Controllers\LogerProfileEntityController;
use App\Domains\LogerProfile\Http\Controllers\ProfileWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified', 'loger.concerns:profiles'])->group(function () {
    Route::get('/loger-profiles/overview', [LogerProfileController::class, 'workspaceOverview'])->name('loger-profiles.overview');
    Route::get('/loger-profiles/{profileId}/preview', [ProfileWorkspaceController::class, 'show'])->name('loger-profiles.preview');
    Route::prefix('/loger-profiles/{profileId}/workspace')->name('profile-workspace.')->controller(ProfileWorkspaceController::class)->group(function () {
        Route::post('/boards', 'createBoard')->name('boards.store');
        Route::patch('/boards/{boardId}', 'updateBoard')->name('boards.update');
        Route::post('/boards/{boardId}/notebooks', 'createNotebook')->name('notebooks.store');
        Route::post('/boards/{boardId}/pages', 'createPage')->name('pages.store');
        Route::patch('/items/{itemId}', 'updateItem')->name('items.update');
        Route::put('/links', 'saveLinks')->name('links.update');
        Route::patch('/occurrences/{occurrenceId}', 'registerOccurrence')->name('occurrences.update');
    });
    // Route::resource('/loger-profiles', [LogerProfileController::class, 'index'])->name('profiles.index');
    Route::get('/loger-profiles/{profileId}', [ProfileWorkspaceController::class, 'show'])->name('loger-profiles.show');
    Route::resource('/loger-profiles', LogerProfileController::class)->except('show');
    Route::resource('/loger-profiles/{profileId}/entities', LogerProfileEntityController::class);
    Route::get('/loger-profiles/{profileId}/transactions', [LogerProfileController::class, 'transactions']);
    Route::get('/relationships/overview', [LogerProfileController::class, 'overview'])->name('relationships-overview');
    Route::get('/relationships/{profileName}', [LogerProfileController::class, 'relationships'])->name('relationships.profile');
});

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::get('/relationships-mock', fn () => inertia('Relationships/CoupleSupportMock'))
        ->name('relationships.mock');
});
