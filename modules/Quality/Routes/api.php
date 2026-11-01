<?php

use Illuminate\Support\Facades\Route;
use Modules\Quality\Http\Controllers\Api\QualityApiController;

// Read-only: /api/v1/quality/*  (staff tokens; the hospital is the X-Organization-Id header and only one the user belongs to). Nothing here names a reporter.
Route::prefix('quality')->name('api.quality.')->middleware('throttle:60,1')->group(function () {
    Route::get('overview', [QualityApiController::class, 'overview'])->name('overview');
    Route::get('indicators', [QualityApiController::class, 'indicators'])->name('indicators');
    Route::get('incidents', [QualityApiController::class, 'incidents'])->name('incidents');
    Route::get('actions', [QualityApiController::class, 'actions'])->name('actions');
});
