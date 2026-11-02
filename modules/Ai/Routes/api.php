<?php

use Illuminate\Support\Facades\Route;
use Modules\Ai\Http\Controllers\Api\AiApiController;

// Read-only: /api/v1/ai/*  (staff tokens; the hospital is the X-Organization-Id header and only one the user belongs to). It lists what exists and what happened; it cannot ask the AI anything or change anything.
Route::prefix('ai')->name('api.ai.')->middleware('throttle:60,1')->group(function () {
    Route::get('use-cases', [AiApiController::class, 'useCases'])->name('usecases');
    Route::get('outputs', [AiApiController::class, 'outputs'])->name('outputs');
    Route::get('metrics', [AiApiController::class, 'metrics'])->name('metrics');
});
