<?php

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Http\Controllers\Api\AnalyticsApiController;

// Read-only: /api/v1/analytics/*  (staff tokens; the hospital is the X-Organization-Id header and only one the user belongs to)
Route::prefix('analytics')->name('api.analytics.')->middleware('throttle:60,1')->group(function () {
    Route::get('catalog', [AnalyticsApiController::class, 'catalog'])->name('catalog');
    Route::get('kpis', [AnalyticsApiController::class, 'kpis'])->name('kpis');
    Route::post('query', [AnalyticsApiController::class, 'query'])->name('query');
});
