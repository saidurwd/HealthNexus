<?php

use Illuminate\Support\Facades\Route;
use Modules\Audit\Http\Controllers\AuditLogController;

Route::middleware(['can:audit.view'])->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit.show');
});
