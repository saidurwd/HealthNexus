<?php

use Illuminate\Support\Facades\Route;
use Modules\Watch\Http\Controllers\Web\ConditionController;
use Modules\Watch\Http\Controllers\Web\SignalController;
use Modules\Watch\Http\Controllers\Web\WatchHomeController;

// Disease watch: /watch/*. Every screen checks its own right.
Route::prefix('watch')->name('watch.')->group(function () {
    Route::get('/', [WatchHomeController::class, 'index'])->name('index');
    Route::post('scan', [WatchHomeController::class, 'scan'])->name('scan');

    Route::get('signals', [SignalController::class, 'index'])->name('signals');
    Route::get('signals/{signal}', [SignalController::class, 'show'])->name('signals.show');
    Route::post('signals/{signal}/investigate', [SignalController::class, 'investigate'])->name('signals.investigate');
    Route::post('signals/{signal}/decide', [SignalController::class, 'decide'])->name('signals.decide');
    Route::post('signals/{signal}/report', [SignalController::class, 'report'])->name('signals.report');

    Route::get('conditions', [ConditionController::class, 'index'])->name('conditions');
    Route::post('conditions/install', [ConditionController::class, 'install'])->name('conditions.install');
    Route::post('conditions/{condition}', [ConditionController::class, 'update'])->whereNumber('condition')->name('conditions.update');
});
