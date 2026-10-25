<?php

use Illuminate\Support\Facades\Route;
use Modules\Icu\Http\Controllers\Api\V1\IcuCareController;
use Modules\Icu\Http\Controllers\Api\V1\IcuClinicalApiController;

Route::prefix('icu')->name('api.icu.')->group(function () {
    Route::get('dashboard', [IcuCareController::class, 'dashboard'])->name('dashboard');
    Route::get('bed-board', [IcuCareController::class, 'bedBoard'])->name('bed-board');

    Route::get('episodes', [IcuCareController::class, 'episodes'])->name('episodes.index');
    Route::get('episodes/{episode}', [IcuCareController::class, 'showEpisode'])->name('episodes.show');
    Route::post('episodes/{episode}/transfer', [IcuCareController::class, 'transfer'])->name('episodes.transfer');

    Route::get('admission-requests', [IcuCareController::class, 'admissionRequests'])->name('admission-requests.index');
    Route::post('admission-requests', [IcuCareController::class, 'storeAdmissionRequest'])->name('admission-requests.store');

    Route::get('episodes/{episode}/observations', [IcuClinicalApiController::class, 'observations'])->name('episodes.observations.index');
    Route::post('episodes/{episode}/observations', [IcuClinicalApiController::class, 'storeObservation'])->name('episodes.observations.store');

    Route::get('alerts', [IcuClinicalApiController::class, 'alerts'])->name('alerts.index');
    Route::post('alerts/{alert}/acknowledge', [IcuClinicalApiController::class, 'acknowledgeAlert'])->name('alerts.acknowledge');
});
