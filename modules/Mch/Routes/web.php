<?php

use Illuminate\Support\Facades\Route;
use Modules\Mch\Http\Controllers\Web\ImmunisationController;
use Modules\Mch\Http\Controllers\Web\MchHomeController;
use Modules\Mch\Http\Controllers\Web\PregnancyController;

// Mothers and babies: /mch/*. Every screen checks its own right.
Route::prefix('mch')->name('mch.')->group(function () {
    Route::get('/', [MchHomeController::class, 'index'])->name('index');

    Route::get('pregnancies', [PregnancyController::class, 'index'])->name('pregnancies');
    Route::get('pregnancies/new', [PregnancyController::class, 'create'])->name('pregnancies.create');
    Route::post('pregnancies', [PregnancyController::class, 'store'])->name('pregnancies.store');
    Route::get('pregnancies/{pregnancy}', [PregnancyController::class, 'show'])->name('pregnancies.show');
    Route::post('pregnancies/{pregnancy}/visits', [PregnancyController::class, 'visit'])->name('pregnancies.visit');
    Route::post('pregnancies/{pregnancy}/delivery', [PregnancyController::class, 'deliver'])->name('pregnancies.deliver');
    Route::post('pregnancies/{pregnancy}/close', [PregnancyController::class, 'close'])->name('pregnancies.close');

    Route::get('immunisation', [ImmunisationController::class, 'index'])->name('immunisation');
    Route::get('immunisation/{child}', [ImmunisationController::class, 'card'])->whereNumber('child')->name('immunisation.card');
    Route::post('immunisation/{child}', [ImmunisationController::class, 'give'])->whereNumber('child')->name('immunisation.give');
});
