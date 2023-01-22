<?php

use App\Domains\Housing\Contracts\OccurrenceNotifyTypes;
use App\Domains\Housing\Http\Controllers\OccurrenceController;
use App\Domains\Housing\Http\Controllers\ProjectController;
use App\Domains\Housing\Models\OccurrenceCheck;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::get('/housing', ProjectController::class);
    Route::resource('/housing/occurrence', OccurrenceController::class);
    Route::controller(OccurrenceController::class)->group(function () {
        Route::post('/housing/occurrence/{occurrence}/instances', 'addInstance');
        Route::delete('/housing/occurrence/{occurrence}/instances', 'removeLastInstance');
        Route::get('/housing/occurrence/{occurrence}/preview', 'automationPreview');
        Route::post('/housing/occurrence/{occurrence}/load', 'automationLoad');
        Route::post('/housing/occurrence/{occurrence}/sync', 'sync');
        Route::get('housing/occurrence-export', 'export')->name('occurrences.export');
    });

    Route::get('/housing/test',function () {
        $occurrencesOnLast = OccurrenceCheck::getForNotificationType(OccurrenceNotifyTypes::LAST);
        $occurrencesOnAvg = OccurrenceCheck::getForNotificationType(OccurrenceNotifyTypes::AVG);

        return [
            $occurrencesOnAvg,
            $occurrencesOnLast
        ];
    });
});


