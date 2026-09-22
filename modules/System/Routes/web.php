<?php

use Illuminate\Support\Facades\Route;
use Modules\System\Http\Controllers\AboutController;
use Modules\System\Http\Controllers\QueueMonitorController;
use Modules\System\Http\Controllers\ScheduledJobsController;
use Modules\System\Http\Controllers\SystemHealthController;

Route::middleware(['can:system.health.view'])->group(function () {
    Route::get('system/health', [SystemHealthController::class, 'index'])->name('system.health');
});

Route::middleware(['can:system.queue.view'])->group(function () {
    Route::get('system/queue', [QueueMonitorController::class, 'index'])->name('system.queue');
});

Route::middleware(['can:system.scheduler.view'])->group(function () {
    Route::get('system/scheduled-jobs', [ScheduledJobsController::class, 'index'])->name('system.scheduled-jobs');
});

Route::middleware(['can:system.health.view'])->group(function () {
    Route::get('system/about', [AboutController::class, 'index'])->name('system.about');
});
