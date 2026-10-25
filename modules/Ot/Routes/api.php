<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureCompanyAccess;
use Illuminate\Support\Facades\Route;
use Modules\Ot\Http\Controllers\Api\V1\OtCareController;
use Modules\Ot\Http\Controllers\Api\V1\OtClinicalController;

Route::middleware([EnsureCompanyAccess::class, EnsureBranchAccess::class])->prefix('ot')->group(function () {
    Route::get('/dashboard', [OtCareController::class, 'dashboard']);

    Route::get('/surgery-requests', [OtCareController::class, 'requests']);
    Route::post('/surgery-requests', [OtCareController::class, 'storeRequest']);
    Route::post('/surgery-requests/{surgeryRequest}/approve', [OtCareController::class, 'approveRequest']);

    Route::get('/surgeries', [OtCareController::class, 'surgeries']);
    Route::get('/surgeries/{surgery}', [OtCareController::class, 'surgery']);
    Route::post('/surgeries/{surgery}/schedule', [OtCareController::class, 'schedule']);
    Route::post('/surgeries/{surgery}/cancel', [OtCareController::class, 'cancel']);

    Route::get('/surgeries/{surgery}/preop-checklist', [OtClinicalController::class, 'preoperativeChecklist']);
    Route::post('/preop-checklist-items/{item}', [OtClinicalController::class, 'completePreoperativeChecklistItem']);

    Route::post('/surgeries/{surgery}/safety-checklist/{phase}', [OtClinicalController::class, 'initializeSafetyChecklist']);
    Route::post('/safety-checklist-items/{item}', [OtClinicalController::class, 'completeSafetyChecklistItem']);
    Route::post('/safety-checklists/{checklist}/complete', [OtClinicalController::class, 'completeSafetyChecklistPhase']);

    Route::post('/surgeries/{surgery}/receive', [OtClinicalController::class, 'receive']);
    Route::post('/surgeries/{surgery}/start', [OtClinicalController::class, 'start']);
    Route::post('/surgeries/{surgery}/complete', [OtClinicalController::class, 'complete']);

    Route::post('/surgeries/{surgery}/operative-note', [OtClinicalController::class, 'storeOperativeNote']);
    Route::post('/operative-notes/{note}/finalize', [OtClinicalController::class, 'finalizeOperativeNote']);

    Route::get('/surgeries/{surgery}/recovery', [OtClinicalController::class, 'recovery']);
    Route::post('/recovery/{recovery}/transfer', [OtClinicalController::class, 'completeRecoveryTransfer']);
});
