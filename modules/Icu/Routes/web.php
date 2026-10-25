<?php

use Illuminate\Support\Facades\Route;
use Modules\Icu\Http\Controllers\Web\IcuAdmissionRequestController;
use Modules\Icu\Http\Controllers\Web\IcuAlertController;
use Modules\Icu\Http\Controllers\Web\IcuBedBoardController;
use Modules\Icu\Http\Controllers\Web\IcuClinicalController;
use Modules\Icu\Http\Controllers\Web\IcuCriticalEventController;
use Modules\Icu\Http\Controllers\Web\IcuDashboardController;
use Modules\Icu\Http\Controllers\Web\IcuDischargeController;
use Modules\Icu\Http\Controllers\Web\IcuEpisodeController;
use Modules\Icu\Http\Controllers\Web\IcuFamilyCommunicationController;
use Modules\Icu\Http\Controllers\Web\IcuProcedureController;
use Modules\Icu\Http\Controllers\Web\IcuReportingController;
use Modules\Icu\Http\Controllers\Web\IcuSettingsController;

Route::middleware(['can:icu.dashboard.view'])->group(function () {
    Route::get('icu', [IcuDashboardController::class, 'index'])->name('icu.dashboard');
});

Route::prefix('icu')->name('icu.')->group(function () {
    Route::middleware(['can:icu.bed.view'])->group(function () {
        Route::get('bed-board', [IcuBedBoardController::class, 'index'])->name('bed-board');
    });

    // Admission requests — /create must be registered before the /{icuRequest} wildcard.
    Route::middleware(['can:icu.admission_request.create'])->group(function () {
        Route::get('admission-requests/create', [IcuAdmissionRequestController::class, 'create'])->name('admission-requests.create');
        Route::post('admission-requests', [IcuAdmissionRequestController::class, 'store'])->name('admission-requests.store');
    });
    Route::middleware(['can:icu.admission_request.view'])->group(function () {
        Route::get('admission-requests', [IcuAdmissionRequestController::class, 'index'])->name('admission-requests.index');
        Route::get('admission-requests/{icuRequest}', [IcuAdmissionRequestController::class, 'show'])->name('admission-requests.show');
    });
    Route::middleware(['can:icu.admission_request.approve'])->group(function () {
        Route::post('admission-requests/{icuRequest}/approve', [IcuAdmissionRequestController::class, 'approve'])->name('admission-requests.approve');
    });
    Route::middleware(['can:icu.admission_request.reject'])->group(function () {
        Route::post('admission-requests/{icuRequest}/reject', [IcuAdmissionRequestController::class, 'reject'])->name('admission-requests.reject');
    });
    Route::middleware(['can:icu.admission_request.cancel'])->group(function () {
        Route::post('admission-requests/{icuRequest}/cancel', [IcuAdmissionRequestController::class, 'cancel'])->name('admission-requests.cancel');
    });

    // Episodes — same /create-before-/{episode} ordering requirement.
    Route::middleware(['can:icu.episode.create'])->group(function () {
        Route::get('episodes/create', [IcuEpisodeController::class, 'create'])->name('episodes.create');
        Route::post('episodes', [IcuEpisodeController::class, 'store'])->name('episodes.store');
    });
    Route::middleware(['can:icu.episode.view'])->group(function () {
        Route::get('episodes', [IcuEpisodeController::class, 'index'])->name('episodes.index');
        Route::get('episodes/{episode}', [IcuEpisodeController::class, 'show'])->name('episodes.show');
    });
    Route::middleware(['can:icu.episode.transfer'])->group(function () {
        Route::post('episodes/{episode}/transfer', [IcuEpisodeController::class, 'transfer'])->name('episodes.transfer');
    });

    // Clinical documentation (assessment/flowsheet/notes/handover/ventilator/infusion/device/fluid/nutrition/score).
    Route::middleware(['can:icu.episode.view'])->group(function () {
        Route::get('episodes/{episode}/assessment', [IcuClinicalController::class, 'assessment'])->name('episodes.assessment');
        Route::post('episodes/{episode}/assessment', [IcuClinicalController::class, 'storeAssessment'])->name('episodes.assessment.store');
        Route::post('assessments/{assessment}/finalize', [IcuClinicalController::class, 'finalizeAssessment'])->name('assessments.finalize');

        Route::get('episodes/{episode}/flowsheet', [IcuClinicalController::class, 'flowsheet'])->name('episodes.flowsheet');
        Route::post('episodes/{episode}/observations', [IcuClinicalController::class, 'storeObservation'])->name('episodes.observations.store');

        Route::post('episodes/{episode}/progress-notes', [IcuClinicalController::class, 'storeProgressNote'])->name('episodes.notes.store');
        Route::post('notes/{note}/finalize', [IcuClinicalController::class, 'finalizeProgressNote'])->name('notes.finalize');

        Route::post('episodes/{episode}/handover', [IcuClinicalController::class, 'prepareHandover'])->name('episodes.handover.prepare');
        Route::post('handover/{handover}/acknowledge', [IcuClinicalController::class, 'acknowledgeHandover'])->name('handover.acknowledge');

        Route::post('episodes/{episode}/ventilator', [IcuClinicalController::class, 'storeVentilatorEvent'])->name('episodes.ventilator.store');
        Route::post('episodes/{episode}/respiratory-support', [IcuClinicalController::class, 'storeRespiratorySupport'])->name('episodes.respiratory.store');

        Route::post('episodes/{episode}/infusions', [IcuClinicalController::class, 'storeInfusion'])->name('episodes.infusions.store');
        Route::post('infusions/{infusion}/titrate', [IcuClinicalController::class, 'titrateInfusion'])->name('infusions.titrate');
        Route::post('infusions/{infusion}/stop', [IcuClinicalController::class, 'stopInfusion'])->name('infusions.stop');

        Route::post('episodes/{episode}/devices', [IcuClinicalController::class, 'storeDevice'])->name('episodes.devices.store');
        Route::post('devices/{device}/remove', [IcuClinicalController::class, 'removeDevice'])->name('devices.remove');

        Route::post('episodes/{episode}/fluid-balance', [IcuClinicalController::class, 'storeFluidBalance'])->name('episodes.fluid-balance.store');
        Route::post('episodes/{episode}/nutrition', [IcuClinicalController::class, 'storeNutrition'])->name('episodes.nutrition.store');
        Route::post('episodes/{episode}/scores', [IcuClinicalController::class, 'calculateScore'])->name('episodes.scores.calculate');

        Route::post('episodes/{episode}/family-communication', [IcuFamilyCommunicationController::class, 'store'])->name('episodes.family-communication.store');
    });

    // Procedures.
    Route::middleware(['can:icu.procedure.view'])->group(function () {
        Route::get('procedures/create', [IcuProcedureController::class, 'create'])->name('procedures.create');
        Route::post('procedures', [IcuProcedureController::class, 'store'])->name('procedures.store');
        Route::get('procedures', [IcuProcedureController::class, 'index'])->name('procedures.index');
    });
    Route::middleware(['can:icu.procedure.create'])->group(function () {
        Route::post('episodes/{episode}/procedures', [IcuProcedureController::class, 'record'])->name('episodes.procedures.record');
    });

    // Critical alerts.
    Route::middleware(['can:icu.alert.view'])->group(function () {
        Route::get('alerts', [IcuAlertController::class, 'index'])->name('alerts.index');
    });
    Route::middleware(['can:icu.alert.acknowledge'])->group(function () {
        Route::post('alerts/{alert}/acknowledge', [IcuAlertController::class, 'acknowledge'])->name('alerts.acknowledge');
    });
    Route::middleware(['can:icu.alert.manage'])->group(function () {
        Route::post('alerts/{alert}/escalate', [IcuAlertController::class, 'escalate'])->name('alerts.escalate');
    });

    // Code Blue / critical events.
    Route::middleware(['can:icu.critical_event.view'])->group(function () {
        Route::get('critical-events', [IcuCriticalEventController::class, 'index'])->name('critical-events.index');
    });
    Route::middleware(['can:icu.critical_event.create'])->group(function () {
        Route::post('episodes/{episode}/critical-events', [IcuCriticalEventController::class, 'store'])->name('episodes.critical-events.store');
    });
    Route::middleware(['can:icu.critical_event.finalize'])->group(function () {
        Route::post('critical-events/{event}/finalize', [IcuCriticalEventController::class, 'finalize'])->name('critical-events.finalize');
    });

    // Discharge.
    Route::middleware(['can:icu.discharge.create'])->group(function () {
        Route::get('episodes/{episode}/discharge/create', [IcuDischargeController::class, 'create'])->name('discharge.create');
        Route::post('episodes/{episode}/discharge', [IcuDischargeController::class, 'store'])->name('discharge.store');
    });
    Route::middleware(['can:icu.discharge.view'])->group(function () {
        Route::get('discharge/{summary}', [IcuDischargeController::class, 'show'])->name('discharge.show');
    });
    Route::middleware(['can:icu.discharge.finalize'])->group(function () {
        Route::post('discharge/{summary}/finalize', [IcuDischargeController::class, 'finalize'])->name('discharge.finalize');
    });

    // Reports.
    Route::middleware(['can:icu.reports.view'])->group(function () {
        Route::get('reports/occupancy', [IcuReportingController::class, 'occupancy'])->name('reports.occupancy');
        Route::get('reports/utilization', [IcuReportingController::class, 'utilization'])->name('reports.utilization');
        Route::get('reports/outcomes', [IcuReportingController::class, 'outcomes'])->name('reports.outcomes');
    });

    // Settings.
    Route::middleware(['can:icu.settings.manage'])->group(function () {
        Route::get('settings', [IcuSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [IcuSettingsController::class, 'update'])->name('settings.update');
    });
});
