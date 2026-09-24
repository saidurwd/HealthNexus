<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureCompanyAccess;
use Illuminate\Support\Facades\Route;
use Modules\Nursing\Http\Controllers\Api\V1\NursingCareController;
use Modules\Nursing\Http\Controllers\Api\V1\NursingMarController;

Route::middleware([EnsureCompanyAccess::class, EnsureBranchAccess::class])->prefix('nursing')->group(function () {
    Route::get('/dashboard', [NursingCareController::class, 'dashboard']);
    Route::get('/episodes', [NursingCareController::class, 'episodes']);
    Route::get('/episodes/{episode}', [NursingCareController::class, 'episode']);

    Route::post('/episodes/{episode}/vitals', [NursingCareController::class, 'storeVital']);
    Route::post('/episodes/{episode}/tasks', [NursingCareController::class, 'storeTask']);
    Route::post('/tasks/{task}/complete', [NursingCareController::class, 'completeTask']);

    Route::post('/episodes/{episode}/notes', [NursingCareController::class, 'storeNote']);
    Route::post('/notes/{note}/finalize', [NursingCareController::class, 'finalizeNote']);
    Route::post('/notes/{note}/amend', [NursingCareController::class, 'amendNote']);

    Route::post('/episodes/{episode}/handover', [NursingCareController::class, 'storeHandover']);
    Route::post('/handover/{handover}/acknowledge', [NursingCareController::class, 'acknowledgeHandover']);

    Route::post('/episodes/{episode}/escalations', [NursingCareController::class, 'storeEscalation']);
    Route::post('/escalations/{escalation}/acknowledge', [NursingCareController::class, 'acknowledgeEscalation']);
    Route::post('/escalations/{escalation}/resolve', [NursingCareController::class, 'resolveEscalation']);

    Route::get('/mar', [NursingMarController::class, 'index']);
    Route::get('/mar/{mar}', [NursingMarController::class, 'show']);
    Route::post('/mar/{mar}/administer', [NursingMarController::class, 'administer']);
    Route::post('/mar/{mar}/hold', [NursingMarController::class, 'hold']);
    Route::post('/mar/{mar}/refuse', [NursingMarController::class, 'refuse']);
    Route::post('/mar/{mar}/omit', [NursingMarController::class, 'omit']);
    Route::post('/mar/{mar}/correct', [NursingMarController::class, 'correct']);
});
