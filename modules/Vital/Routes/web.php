<?php

use Illuminate\Support\Facades\Route;
use Modules\Vital\Http\Controllers\Web\BirthController;
use Modules\Vital\Http\Controllers\Web\DeathController;
use Modules\Vital\Http\Controllers\Web\MedicoLegalController;
use Modules\Vital\Http\Controllers\Web\VitalHomeController;

// Births, deaths and police cases: /admin/vital/*. Every screen checks its own right; the police-case section is only for those who hold the medico-legal right.
Route::prefix('vital')->name('vital.')->group(function () {
    Route::get('/', [VitalHomeController::class, 'index'])->name('index');

    Route::get('births', [BirthController::class, 'index'])->name('births');
    Route::get('births/new', [BirthController::class, 'create'])->name('births.create');
    Route::post('births', [BirthController::class, 'store'])->name('births.store');
    Route::get('births/{birth}', [BirthController::class, 'show'])->name('births.show');
    Route::post('births/{birth}/issue', [BirthController::class, 'issue'])->name('births.issue');
    Route::post('births/{birth}/register', [BirthController::class, 'register'])->name('births.register');
    Route::post('births/{birth}/cancel', [BirthController::class, 'cancel'])->name('births.cancel');

    Route::get('deaths', [DeathController::class, 'index'])->name('deaths');
    Route::get('deaths/new', [DeathController::class, 'create'])->name('deaths.create');
    Route::post('deaths', [DeathController::class, 'store'])->name('deaths.store');
    Route::get('deaths/{death}', [DeathController::class, 'show'])->name('deaths.show');
    Route::post('deaths/{death}/certify', [DeathController::class, 'certify'])->name('deaths.certify');
    Route::post('deaths/{death}/issue', [DeathController::class, 'issue'])->name('deaths.issue');
    Route::post('deaths/{death}/cancel', [DeathController::class, 'cancel'])->name('deaths.cancel');

    Route::get('police-cases', [MedicoLegalController::class, 'index'])->name('medicolegal');
    Route::get('police-cases/new', [MedicoLegalController::class, 'create'])->name('medicolegal.create');
    Route::post('police-cases', [MedicoLegalController::class, 'store'])->name('medicolegal.store');
    Route::get('police-cases/{case}', [MedicoLegalController::class, 'show'])->name('medicolegal.show');
    Route::post('police-cases/{case}/inform', [MedicoLegalController::class, 'inform'])->name('medicolegal.inform');
    Route::post('police-cases/{case}/postmortem', [MedicoLegalController::class, 'postmortem'])->name('medicolegal.postmortem');
    Route::post('police-cases/{case}/close', [MedicoLegalController::class, 'close'])->name('medicolegal.close');
});
