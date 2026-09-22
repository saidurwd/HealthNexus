<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\Web\SettingsController;

Route::middleware(['can:settings.view'])->group(function () {
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
});

Route::middleware(['can:settings.update'])->group(function () {
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
});
