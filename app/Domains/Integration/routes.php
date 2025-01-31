<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Integration\Http\Controllers\WhatsappController;


Route::controller(WhatsappController::class)->group(function () {
    Route::post('/webhook/whatsapp', 'handle');
    Route::get('/webhook/whatsapp', 'verify');
});
