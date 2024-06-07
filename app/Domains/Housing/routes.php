<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Housing\Contracts\OccurrenceNotifyTypes;
use App\Domains\Housing\Http\Controllers\ProjectController;
use App\Domains\Housing\Http\Controllers\OccurrenceController;

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::get('/housing', ProjectController::class)->name('housing.overview');
    Route::resource('/housing/occurrence', OccurrenceController::class);

    Route::controller(OccurrenceController::class)->group(function () {
        Route::post('/housing/occurrences/{occurrence}/instances', 'addInstance');
        Route::delete('/housing/occurrences/{occurrence}/instances', 'removeLastInstance');
        Route::delete('/housing/occurrences/{occurrence}', 'destroy');
        Route::get('/housing/occurrences/{occurrence}/preview', 'automationPreview');
        Route::post('/housing/occurrences/{occurrence}/load', 'automationLoad');
        Route::post('/housing/occurrences/{occurrence}/sync', 'sync');
        Route::post('/housing/occurrences/sync-all', 'syncAll');
        Route::get('housing/occurrence-export', 'export')->name('housing.occurrences.export');
        Route::post('housing/occurrence-import', 'import')->name('housing.occurrences.import');
    });

    Route::get('/housing/test', function () {
        $occurrencesOnLast = Occurrence::getForNotificationType(OccurrenceNotifyTypes::LAST);
        $occurrencesOnAvg = Occurrence::getForNotificationType(OccurrenceNotifyTypes::AVG);

        return [
            $occurrencesOnAvg,
            $occurrencesOnLast,
        ];
    });
});
