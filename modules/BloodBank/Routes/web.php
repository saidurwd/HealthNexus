<?php

use Illuminate\Support\Facades\Route;
use Modules\BloodBank\Http\Controllers\Web\BloodDashboardController;
use Modules\BloodBank\Http\Controllers\Web\BloodDonorController;
use Modules\BloodBank\Http\Controllers\Web\BloodInventoryController;
use Modules\BloodBank\Http\Controllers\Web\BloodRequestController;
use Modules\BloodBank\Http\Controllers\Web\BloodSafetyController;
use Modules\BloodBank\Http\Controllers\Web\BloodTransfusionController;

Route::prefix('blood-bank')->name('blood.')->group(function () {
    Route::get('/', [BloodDashboardController::class, 'index'])->name('dashboard');

    Route::get('donors', [BloodDonorController::class, 'index'])->name('donors.index');
    Route::get('donors/create', [BloodDonorController::class, 'create'])->name('donors.create');
    Route::post('donors', [BloodDonorController::class, 'store'])->name('donors.store');
    Route::get('donors/{donor}', [BloodDonorController::class, 'show'])->name('donors.show');
    Route::post('donors/{donor}/screening', [BloodDonorController::class, 'screen'])->name('donors.screen');
    Route::post('donors/{donor}/donations', [BloodDonorController::class, 'startDonation'])->name('donors.donate');
    Route::get('donations/{donation}', [BloodDonorController::class, 'showDonation'])->name('donations.show');
    Route::post('donations/{donation}/complete', [BloodDonorController::class, 'completeDonation'])->name('donations.complete');
    Route::post('donations/{donation}/abort', [BloodDonorController::class, 'abortDonation'])->name('donations.abort');
    Route::post('donations/{donation}/adverse-events', [BloodDonorController::class, 'adverseEvent'])->name('donations.adverse');
    Route::post('donations/{donation}/observation', [BloodDonorController::class, 'finishObservation'])->name('donations.observation');
    Route::post('donations/{donation}/separate', [BloodDonorController::class, 'separate'])->name('donations.separate');
    Route::post('donations/{donation}/sync-tests', [BloodDonorController::class, 'syncTests'])->name('donations.sync');
    Route::post('donations/{donation}/release', [BloodDonorController::class, 'release'])->name('donations.release');

    Route::get('inventory', [BloodInventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/storage', [BloodInventoryController::class, 'storage'])->name('inventory.storage');
    Route::post('inventory/storage/{location}/temperature', [BloodInventoryController::class, 'temperature'])->name('inventory.temperature');
    Route::get('units/{unit}', [BloodInventoryController::class, 'show'])->name('units.show');
    Route::post('units/{unit}/discard', [BloodInventoryController::class, 'discard'])->name('units.discard');

    Route::get('requests', [BloodRequestController::class, 'index'])->name('requests.index');
    Route::get('requests/create', [BloodRequestController::class, 'create'])->name('requests.create');
    Route::post('requests', [BloodRequestController::class, 'store'])->name('requests.store');
    Route::post('requests/mtp', [BloodRequestController::class, 'mtp'])->name('requests.mtp');
    Route::get('requests/{bloodRequest}', [BloodRequestController::class, 'show'])->name('requests.show');
    Route::post('requests/{bloodRequest}/sample', [BloodRequestController::class, 'sample'])->name('requests.sample');
    Route::post('requests/{bloodRequest}/reserve', [BloodRequestController::class, 'reserve'])->name('requests.reserve');
    Route::post('requests/{bloodRequest}/issue', [BloodRequestController::class, 'issue'])->name('requests.issue');
    Route::post('requests/{bloodRequest}/emergency', [BloodRequestController::class, 'emergency'])->name('requests.emergency');
    Route::post('requests/{bloodRequest}/cancel', [BloodRequestController::class, 'cancel'])->name('requests.cancel');
    Route::post('issues/{issue}/return', [BloodRequestController::class, 'returnUnit'])->name('issues.return');
    Route::post('issues/{issue}/retrospective', [BloodRequestController::class, 'retrospective'])->name('issues.retrospective');

    Route::get('transfusions', [BloodTransfusionController::class, 'index'])->name('transfusions.index');
    Route::post('issues/{issue}/start', [BloodTransfusionController::class, 'start'])->name('transfusions.start');
    Route::get('transfusions/{transfusion}', [BloodTransfusionController::class, 'show'])->name('transfusions.show');
    Route::post('transfusions/{transfusion}/observations', [BloodTransfusionController::class, 'observe'])->name('transfusions.observe');
    Route::post('transfusions/{transfusion}/complete', [BloodTransfusionController::class, 'complete'])->name('transfusions.complete');
    Route::post('transfusions/{transfusion}/reaction', [BloodTransfusionController::class, 'reaction'])->name('transfusions.reaction');

    Route::get('incidents', [BloodSafetyController::class, 'incidents'])->name('incidents.index');
    Route::post('incidents/{incident}/close', [BloodSafetyController::class, 'closeIncident'])->name('incidents.close');
    Route::get('lookback', [BloodSafetyController::class, 'lookback'])->name('lookback');
    Route::post('recall', [BloodSafetyController::class, 'recall'])->name('recall');
    Route::get('reports', [BloodSafetyController::class, 'reports'])->name('reports');
});
