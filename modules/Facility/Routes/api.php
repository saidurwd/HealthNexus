<?php

use Illuminate\Support\Facades\Route;
use Modules\Facility\Http\Controllers\Api\FacilityApiController;

// Read-only: /api/v1/facility/*  (staff tokens; the hospital is the X-Organization-Id header and only one the user belongs to)
Route::prefix('facility')->name('api.facility.')->middleware('throttle:60,1')->group(function () {
    Route::get('overview', [FacilityApiController::class, 'overview'])->name('overview');
    Route::get('assets/{tag}', [FacilityApiController::class, 'asset'])->name('asset');
    Route::get('work-orders', [FacilityApiController::class, 'workOrders'])->name('workorders');
    Route::get('beds-to-clean', [FacilityApiController::class, 'bedsToClean'])->name('cleaning');
});
