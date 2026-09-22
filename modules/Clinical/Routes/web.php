<?php

use Illuminate\Support\Facades\Route;
use Modules\Clinical\Http\Controllers\EncounterController;

Route::middleware('can:manage companies')->group(function () {
    Route::resource('encounters', EncounterController::class);
});
