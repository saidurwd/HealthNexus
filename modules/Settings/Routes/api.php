<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\Api\SettingsController;

Route::middleware(['auth:sanctum', 'can:settings.view'])->group(function () {
    Route::get('/settings', SettingsController::class);
});
