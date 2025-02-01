<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\System\IntegrationController;
use App\Domains\Integration\Http\Controllers\WhatsappController;


Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::get('/integrations', IntegrationController::class)->name('settings.integrations');
    Route::post('/integrations/google', [IntegrationController::class, 'google'])->name('services.google');
});

Route::controller(WhatsappController::class)->group(function () {
    Route::post('/webhook/whatsapp', 'handle');
    Route::get('/webhook/whatsapp', 'verify');
});
