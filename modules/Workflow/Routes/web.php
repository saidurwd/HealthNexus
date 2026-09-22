<?php

use Illuminate\Support\Facades\Route;
use Modules\Workflow\Http\Controllers\WorkflowInstanceController;

Route::middleware(['can:workflow.view'])->group(function () {
    Route::get('workflow', [WorkflowInstanceController::class, 'index'])->name('workflow.index');
    Route::get('workflow/{instance}', [WorkflowInstanceController::class, 'show'])->name('workflow.show');
});

Route::middleware(['can:workflow.act'])->group(function () {
    Route::post('workflow/{instance}/approve', [WorkflowInstanceController::class, 'approve'])->name('workflow.approve');
    Route::post('workflow/{instance}/reject', [WorkflowInstanceController::class, 'reject'])->name('workflow.reject');
    Route::post('workflow/{instance}/return', [WorkflowInstanceController::class, 'returnForRevision'])->name('workflow.return');
});
