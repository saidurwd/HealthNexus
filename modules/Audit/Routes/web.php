<?php

use Illuminate\Support\Facades\Route;
use Modules\Audit\Http\Controllers\ActivityLogController;
use Modules\Audit\Http\Controllers\AuditLogController;
use Modules\Audit\Http\Controllers\SecurityEventController;

Route::middleware(['can:audit.view'])->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit.show');
});

Route::middleware(['can:activity.view'])->group(function () {
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity.index');
    Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity.show');
});

Route::middleware(['can:security.event.view'])->group(function () {
    Route::get('security-events', [SecurityEventController::class, 'index'])->name('security.index');
    Route::get('security-events/{securityEvent}', [SecurityEventController::class, 'show'])->name('security.show');
    Route::put('security-events/{securityEvent}/resolve', [SecurityEventController::class, 'resolve'])->name('security.resolve')->middleware('can:security.event.resolve');
});
