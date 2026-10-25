<?php

use Illuminate\Support\Facades\Route;
use Modules\Ot\Http\Controllers\Web\OtDashboardController;
use Modules\Ot\Http\Controllers\Web\OtImplantController;
use Modules\Ot\Http\Controllers\Web\OtOperativeController;
use Modules\Ot\Http\Controllers\Web\OtPreoperativeController;
use Modules\Ot\Http\Controllers\Web\OtProcedureController;
use Modules\Ot\Http\Controllers\Web\OtRecoveryController;
use Modules\Ot\Http\Controllers\Web\OtReportingController;
use Modules\Ot\Http\Controllers\Web\OtSafetyChecklistController;
use Modules\Ot\Http\Controllers\Web\OtSettingsController;
use Modules\Ot\Http\Controllers\Web\OtSpecimenController;
use Modules\Ot\Http\Controllers\Web\OtSurgeryController;
use Modules\Ot\Http\Controllers\Web\OtSurgeryRequestController;
use Modules\Ot\Http\Controllers\Web\OtSurgicalTeamController;
use Modules\Ot\Http\Controllers\Web\OtTheatreController;

Route::middleware(['can:ot.dashboard.view'])->group(function () {
    Route::get('ot', [OtDashboardController::class, 'index'])->name('ot.dashboard');
});

Route::prefix('ot')->name('ot.')->group(function () {
    // Surgery requests — /create must be registered before the /{surgeryRequest} wildcard.
    Route::middleware(['can:ot.surgery.create'])->group(function () {
        Route::get('surgery-requests/create', [OtSurgeryRequestController::class, 'create'])->name('surgery-requests.create');
        Route::post('surgery-requests', [OtSurgeryRequestController::class, 'store'])->name('surgery-requests.store');
    });
    Route::middleware(['can:ot.surgery.view'])->group(function () {
        Route::get('surgery-requests', [OtSurgeryRequestController::class, 'index'])->name('surgery-requests.index');
        Route::get('surgery-requests/{surgeryRequest}', [OtSurgeryRequestController::class, 'show'])->name('surgery-requests.show');
    });
    Route::middleware(['can:ot.surgery.approve'])->group(function () {
        Route::post('surgery-requests/{surgeryRequest}/approve', [OtSurgeryRequestController::class, 'approve'])->name('surgery-requests.approve');
        Route::post('surgery-requests/{surgeryRequest}/reject', [OtSurgeryRequestController::class, 'reject'])->name('surgery-requests.reject');
    });
    Route::middleware(['can:ot.surgery.cancel'])->group(function () {
        Route::post('surgery-requests/{surgeryRequest}/cancel', [OtSurgeryRequestController::class, 'cancel'])->name('surgery-requests.cancel');
    });

    // Surgeries — same /create-before-/{surgery} ordering requirement.
    Route::middleware(['can:ot.surgery.create'])->group(function () {
        Route::get('surgeries/create', [OtSurgeryController::class, 'create'])->name('surgeries.create');
        Route::post('surgeries', [OtSurgeryController::class, 'store'])->name('surgeries.store');
    });
    Route::middleware(['can:ot.surgery.view'])->group(function () {
        Route::get('surgeries', [OtSurgeryController::class, 'index'])->name('surgeries.index');
        Route::get('surgeries/{surgery}', [OtSurgeryController::class, 'show'])->name('surgeries.show');
    });
    Route::middleware(['can:ot.schedule.create'])->group(function () {
        Route::post('surgeries/{surgery}/schedule', [OtSurgeryController::class, 'schedule'])->name('surgeries.schedule');
        Route::post('surgeries/{surgery}/reschedule', [OtSurgeryController::class, 'reschedule'])->name('surgeries.reschedule');
    });
    Route::middleware(['can:ot.surgery.postpone'])->group(function () {
        Route::post('surgeries/{surgery}/postpone', [OtSurgeryController::class, 'postpone'])->name('surgeries.postpone');
    });
    Route::middleware(['can:ot.surgery.cancel'])->group(function () {
        Route::post('surgeries/{surgery}/cancel', [OtSurgeryController::class, 'cancel'])->name('surgeries.cancel');
    });
    Route::middleware(['can:ot.surgery.update'])->group(function () {
        Route::post('surgeries/{surgery}/receive', [OtSurgeryController::class, 'receive'])->name('surgeries.receive');
    });
    Route::middleware(['can:ot.surgery.start'])->group(function () {
        Route::post('surgeries/{surgery}/start', [OtSurgeryController::class, 'start'])->name('surgeries.start');
    });
    Route::middleware(['can:ot.surgery.complete'])->group(function () {
        Route::post('surgeries/{surgery}/complete', [OtSurgeryController::class, 'complete'])->name('surgeries.complete');
    });

    // Surgical team
    Route::middleware(['can:ot.team.assign'])->group(function () {
        Route::post('surgeries/{surgery}/team', [OtSurgicalTeamController::class, 'store'])->name('team.store');
    });
    Route::middleware(['can:ot.team.update'])->group(function () {
        Route::delete('team/{assignment}', [OtSurgicalTeamController::class, 'destroy'])->name('team.destroy');
    });

    // Theatres / complexes
    Route::middleware(['can:ot.theatre.view'])->group(function () {
        Route::get('theatres', [OtTheatreController::class, 'index'])->name('theatres.index');
    });
    Route::middleware(['can:ot.theatre.manage'])->group(function () {
        Route::get('theatres/create', [OtTheatreController::class, 'create'])->name('theatres.create');
        Route::post('theatres', [OtTheatreController::class, 'store'])->name('theatres.store');
        Route::put('theatres/{theatre}', [OtTheatreController::class, 'update'])->name('theatres.update');
        Route::post('complexes', [OtTheatreController::class, 'storeComplex'])->name('complexes.store');
    });

    // Procedures
    Route::middleware(['can:ot.procedure.view'])->group(function () {
        Route::get('procedures', [OtProcedureController::class, 'index'])->name('procedures.index');
        Route::get('procedures/create', [OtProcedureController::class, 'create'])->name('procedures.create');
        Route::post('procedures', [OtProcedureController::class, 'store'])->name('procedures.store');
        Route::put('procedures/{procedure}', [OtProcedureController::class, 'update'])->name('procedures.update');
        Route::post('procedure-categories', [OtProcedureController::class, 'storeCategory'])->name('procedure-categories.store');
    });

    // Preoperative (assessment + checklist + consent + verification)
    Route::middleware(['can:ot.preop.view'])->group(function () {
        Route::get('surgeries/{surgery}/preoperative', [OtPreoperativeController::class, 'show'])->name('preoperative.show');
    });
    Route::middleware(['can:ot.preop.create'])->group(function () {
        Route::post('surgeries/{surgery}/preoperative/assessment', [OtPreoperativeController::class, 'storeAssessment'])->name('preoperative.assessment.store');
        Route::post('preoperative/checklist-items/{item}', [OtPreoperativeController::class, 'completeChecklistItem'])->name('preoperative.checklist-items.complete');
    });
    Route::middleware(['can:ot.preop.complete'])->group(function () {
        Route::post('preoperative/assessments/{assessment}/finalize', [OtPreoperativeController::class, 'finalizeAssessment'])->name('preoperative.assessment.finalize');
        Route::post('preoperative/checklists/{checklist}/complete', [OtPreoperativeController::class, 'completeChecklist'])->name('preoperative.checklist.complete');
    });
    Route::middleware(['can:ot.consent.create'])->group(function () {
        Route::post('surgeries/{surgery}/consent', [OtPreoperativeController::class, 'storeConsent'])->name('consent.store');
    });

    // WHO surgical safety checklist
    Route::middleware(['can:ot.checklist.view'])->group(function () {
        Route::post('surgeries/{surgery}/checklist/{phase}/initialize', [OtSafetyChecklistController::class, 'initialize'])->name('checklist.initialize');
    });
    Route::middleware(['can:ot.checklist.complete'])->group(function () {
        Route::post('checklist-items/{item}', [OtSafetyChecklistController::class, 'completeItem'])->name('checklist.items.complete');
        Route::post('checklists/{checklist}/complete', [OtSafetyChecklistController::class, 'completePhase'])->name('checklist.complete');
    });

    // Intraoperative (operative note + complications + counts)
    Route::middleware(['can:ot.procedure.document'])->group(function () {
        Route::post('surgeries/{surgery}/operative-note', [OtOperativeController::class, 'storeNote'])->name('operative-note.store');
        Route::post('operative-notes/{note}/amend', [OtOperativeController::class, 'amendNote'])->name('operative-note.amend');
        Route::post('surgeries/{surgery}/complications', [OtOperativeController::class, 'storeComplication'])->name('complications.store');
        Route::post('surgeries/{surgery}/counts', [OtOperativeController::class, 'storeCount'])->name('counts.store');
    });
    Route::middleware(['can:ot.procedure.complete'])->group(function () {
        Route::post('operative-notes/{note}/finalize', [OtOperativeController::class, 'finalizeNote'])->name('operative-note.finalize');
    });

    // Specimens / implants
    Route::middleware(['can:ot.specimen.create'])->group(function () {
        Route::post('surgeries/{surgery}/specimens', [OtSpecimenController::class, 'store'])->name('specimens.store');
    });
    Route::middleware(['can:ot.implant.record'])->group(function () {
        Route::post('surgeries/{surgery}/implants', [OtImplantController::class, 'store'])->name('implants.store');
    });

    // Recovery / PACU
    Route::middleware(['can:ot.recovery.view'])->group(function () {
        Route::get('surgeries/{surgery}/recovery', [OtRecoveryController::class, 'show'])->name('recovery.show');
    });
    Route::middleware(['can:ot.recovery.record'])->group(function () {
        Route::post('recovery/{recovery}/vitals', [OtRecoveryController::class, 'recordVitals'])->name('recovery.vitals.store');
        Route::post('recovery/{recovery}/assessment', [OtRecoveryController::class, 'updateAssessment'])->name('recovery.assessment.update');
        Route::post('recovery/{recovery}/ready-for-transfer', [OtRecoveryController::class, 'markReadyForTransfer'])->name('recovery.ready-for-transfer');
    });
    Route::middleware(['can:ot.recovery.complete'])->group(function () {
        Route::post('recovery/{recovery}/transfer', [OtRecoveryController::class, 'completeTransfer'])->name('recovery.transfer');
    });

    // Reports
    Route::middleware(['can:ot.reports.view'])->group(function () {
        Route::get('reports/surgeries', [OtReportingController::class, 'surgeries'])->name('reports.surgeries');
        Route::get('reports/utilization', [OtReportingController::class, 'utilization'])->name('reports.utilization');
        Route::get('reports/complications', [OtReportingController::class, 'complications'])->name('reports.complications');
    });

    // Settings
    Route::middleware(['can:ot.settings.manage'])->group(function () {
        Route::get('settings', [OtSettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [OtSettingsController::class, 'update'])->name('settings.update');
    });
});
