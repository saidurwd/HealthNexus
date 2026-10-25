<?php

use Illuminate\Support\Facades\Route;
use Modules\Emergency\Http\Controllers\Web\EdBoardController;
use Modules\Emergency\Http\Controllers\Web\EdResusController;
use Modules\Emergency\Http\Controllers\Web\EdVisitController;
use Modules\Emergency\Http\Controllers\Web\EdZoneController;

Route::prefix('emergency')->name('ed.')->group(function () {
    Route::get('/', [EdBoardController::class, 'index'])->name('board');
    Route::get('board/data', [EdBoardController::class, 'data'])->name('board.data');

    Route::get('arrivals/create', [EdVisitController::class, 'create'])->name('arrivals.create');
    Route::post('arrivals', [EdVisitController::class, 'store'])->name('arrivals.store');

    Route::get('visits/{visit}', [EdVisitController::class, 'show'])->name('visits.show');
    Route::post('visits/{visit}/triage', [EdVisitController::class, 'triage'])->name('visits.triage');
    Route::post('visits/{visit}/bay', [EdVisitController::class, 'assignBay'])->name('visits.bay');
    Route::delete('visits/{visit}/bay', [EdVisitController::class, 'releaseBay'])->name('visits.bay.release');
    Route::post('visits/{visit}/start', [EdVisitController::class, 'startTreatment'])->name('visits.start');
    Route::put('visits/{visit}/team', [EdVisitController::class, 'team'])->name('visits.team');

    Route::post('visits/{visit}/resus', [EdResusController::class, 'event'])->name('visits.resus');
    Route::post('visits/{visit}/monitor', [EdResusController::class, 'monitor'])->name('visits.monitor');

    Route::get('zones', [EdZoneController::class, 'index'])->name('zones.index');
    Route::post('zones', [EdZoneController::class, 'storeZone'])->name('zones.store');
    Route::post('zones/{zone}/bays', [EdZoneController::class, 'storeBay'])->name('zones.bays.store');
    Route::put('bays/{bay}/status', [EdZoneController::class, 'bayStatus'])->name('bays.status');
});
