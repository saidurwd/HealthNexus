<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\Web\SettingsController;
use Modules\Settings\Http\Controllers\Web\SystemSettingsController;

Route::middleware(['can:settings.view'])->group(function () {
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('settings/system', [SystemSettingsController::class, 'show'])->name('settings.system');
});

Route::middleware(['can:settings.update'])->group(function () {
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
});

Route::middleware(['can:settings.update'])->prefix('settings/system')->name('settings.system.')->group(function () {
    Route::put('general', [SystemSettingsController::class, 'updateGeneral'])->name('general');
    Route::post('branding', [SystemSettingsController::class, 'updateBranding'])->name('branding');
    Route::delete('branding/{kind}', [SystemSettingsController::class, 'removeAsset'])->name('branding.remove');
    Route::put('contact', [SystemSettingsController::class, 'updateContact'])->name('contact');
    Route::put('email', [SystemSettingsController::class, 'updateEmail'])->name('email');
    Route::post('email/test', [SystemSettingsController::class, 'testEmail'])->name('email.test');
});
