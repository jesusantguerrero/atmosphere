<?php

use App\Domains\LogerProfile\Http\Controllers\LogerProfileController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::resource('/loger-profiles', LogerProfileController::class);
});


