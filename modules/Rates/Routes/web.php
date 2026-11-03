<?php

use Illuminate\Support\Facades\Route;
use Modules\Rates\Http\Controllers\Web\BedRateController;

// Bed and cabin rates: /rates/*. Read only.
Route::prefix('rates')->name('rates.')->group(function () {
    Route::get('beds', [BedRateController::class, 'index'])->name('beds');
    Route::get('opd', [BedRateController::class, 'opd'])->name('opd');
});
