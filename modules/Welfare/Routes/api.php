<?php

use Illuminate\Support\Facades\Route;
use Modules\Welfare\Http\Controllers\Api\WelfareApiController;

// Read-only: /api/v1/welfare/*  (staff tokens; the hospital is the X-Organization-Id header and only one the user belongs to). Figures and fund balances only: no patient, income or circumstance leaves through the API.
Route::prefix('welfare')->name('api.welfare.')->middleware('throttle:60,1')->group(function () {
    Route::get('overview', [WelfareApiController::class, 'overview'])->name('overview');
    Route::get('funds', [WelfareApiController::class, 'funds'])->name('funds');
});
