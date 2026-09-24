<?php

use Illuminate\Support\Facades\Route;
use Modules\Nursing\Http\Controllers\Web\NursingAssessmentController;
use Modules\Nursing\Http\Controllers\Web\NursingAssignmentController;
use Modules\Nursing\Http\Controllers\Web\NursingCarePlanController;
use Modules\Nursing\Http\Controllers\Web\NursingClinicalController;
use Modules\Nursing\Http\Controllers\Web\NursingDashboardController;
use Modules\Nursing\Http\Controllers\Web\NursingDischargeChecklistController;
use Modules\Nursing\Http\Controllers\Web\NursingEpisodeController;
use Modules\Nursing\Http\Controllers\Web\NursingEscalationController;
use Modules\Nursing\Http\Controllers\Web\NursingHandoverController;
use Modules\Nursing\Http\Controllers\Web\NursingMarController;
use Modules\Nursing\Http\Controllers\Web\NursingNoteController;
use Modules\Nursing\Http\Controllers\Web\NursingObservationController;
use Modules\Nursing\Http\Controllers\Web\NursingReportingController;
use Modules\Nursing\Http\Controllers\Web\NursingSettingsController;
use Modules\Nursing\Http\Controllers\Web\NursingShiftController;
use Modules\Nursing\Http\Controllers\Web\NursingTaskController;

Route::middleware(['can:nursing.dashboard.view'])->group(function () {
    Route::get('nursing', [NursingDashboardController::class, 'index'])->name('nursing.dashboard');
});

Route::prefix('nursing')->name('nursing.')->group(function () {
    // Episodes (patient nursing workspace hub)
    Route::middleware(['can:nursing.dashboard.view'])->group(function () {
        Route::get('episodes', [NursingEpisodeController::class, 'index'])->name('episodes.index');
        Route::get('episodes/{episode}', [NursingEpisodeController::class, 'show'])->name('episodes.show');
    });

    // Assignments
    Route::middleware(['can:nursing.assignment.create'])->group(function () {
        Route::post('episodes/{episode}/assignments', [NursingAssignmentController::class, 'store'])->name('assignments.store');
    });
    Route::middleware(['can:nursing.assignment.update'])->group(function () {
        Route::delete('assignments/{assignment}', [NursingAssignmentController::class, 'destroy'])->name('assignments.destroy');
    });

    // Shifts
    Route::middleware(['can:nursing.shift.view'])->group(function () {
        Route::get('shifts', [NursingShiftController::class, 'index'])->name('shifts.index');
    });
    Route::middleware(['can:nursing.shift.manage'])->group(function () {
        Route::post('shifts', [NursingShiftController::class, 'store'])->name('shifts.store');
        Route::put('shifts/{shift}', [NursingShiftController::class, 'update'])->name('shifts.update');
    });

    // Assessments
    Route::middleware(['can:nursing.assessment.create'])->group(function () {
        Route::post('episodes/{episode}/assessments', [NursingAssessmentController::class, 'store'])->name('assessments.store');
    });
    Route::middleware(['can:nursing.assessment.update'])->group(function () {
        Route::put('assessments/{assessment}', [NursingAssessmentController::class, 'update'])->name('assessments.update');
    });
    Route::middleware(['can:nursing.assessment.finalize'])->group(function () {
        Route::post('assessments/{assessment}/finalize', [NursingAssessmentController::class, 'finalize'])->name('assessments.finalize');
    });

    // Vitals / observations / pain / risk / intake-output
    Route::middleware(['can:nursing.vitals.view'])->group(function () {
        Route::get('episodes/{episode}/observations', [NursingObservationController::class, 'index'])->name('observations.index');
    });
    Route::middleware(['can:nursing.vitals.create'])->group(function () {
        Route::post('episodes/{episode}/vitals', [NursingObservationController::class, 'storeVital'])->name('vitals.store');
    });
    Route::middleware(['can:nursing.observation.create'])->group(function () {
        Route::post('episodes/{episode}/observations', [NursingObservationController::class, 'storeObservation'])->name('observations.store');
        Route::post('episodes/{episode}/pain', [NursingObservationController::class, 'storePain'])->name('pain.store');
        Route::post('episodes/{episode}/risk', [NursingObservationController::class, 'storeRisk'])->name('risk.store');
        Route::post('episodes/{episode}/intake-output', [NursingObservationController::class, 'storeIntakeOutput'])->name('intake-output.store');
    });
    Route::middleware(['can:nursing.observation.view'])->group(function () {
        Route::get('episodes/{episode}/intake-output', [NursingObservationController::class, 'intakeOutput'])->name('intake-output.index');
    });

    // Care plans
    Route::middleware(['can:nursing.care_plan.create'])->group(function () {
        Route::post('episodes/{episode}/care-plans', [NursingCarePlanController::class, 'store'])->name('care-plans.store');
    });
    Route::middleware(['can:nursing.care_plan.view'])->group(function () {
        Route::get('care-plans/{carePlan}', [NursingCarePlanController::class, 'show'])->name('care-plans.show');
    });
    Route::middleware(['can:nursing.care_plan.update'])->group(function () {
        Route::post('care-plans/{carePlan}/diagnoses', [NursingCarePlanController::class, 'storeDiagnosis'])->name('care-plans.diagnoses.store');
        Route::post('care-plans/{carePlan}/goals', [NursingCarePlanController::class, 'storeGoal'])->name('care-plans.goals.store');
        Route::post('care-plans/{carePlan}/interventions', [NursingCarePlanController::class, 'storeIntervention'])->name('care-plans.interventions.store');
    });
    Route::middleware(['can:nursing.care_plan.complete'])->group(function () {
        Route::post('care-plans/{carePlan}/complete', [NursingCarePlanController::class, 'complete'])->name('care-plans.complete');
    });

    // Tasks
    Route::middleware(['can:nursing.task.view'])->group(function () {
        Route::get('tasks', [NursingTaskController::class, 'index'])->name('tasks.index');
    });
    Route::middleware(['can:nursing.task.create'])->group(function () {
        Route::post('episodes/{episode}/tasks', [NursingTaskController::class, 'store'])->name('tasks.store');
    });
    Route::middleware(['can:nursing.task.complete'])->group(function () {
        Route::post('tasks/{task}/complete', [NursingTaskController::class, 'complete'])->name('tasks.complete');
        Route::post('tasks/{task}/skip', [NursingTaskController::class, 'skip'])->name('tasks.skip');
        Route::post('tasks/{task}/refuse', [NursingTaskController::class, 'refuse'])->name('tasks.refuse');
    });

    // MAR
    Route::middleware(['can:nursing.mar.view'])->group(function () {
        Route::get('mar', [NursingMarController::class, 'index'])->name('mar.index');
        Route::get('mar/{mar}', [NursingMarController::class, 'show'])->name('mar.show');
    });
    Route::middleware(['can:nursing.mar.administer'])->group(function () {
        Route::post('episodes/{episode}/mar', [NursingMarController::class, 'storeAdHoc'])->name('mar.store-ad-hoc');
        Route::post('mar/{mar}/administer', [NursingMarController::class, 'administer'])->name('mar.administer');
    });
    Route::post('mar/{mar}/hold', [NursingMarController::class, 'hold'])->middleware('can:nursing.mar.hold')->name('mar.hold');
    Route::post('mar/{mar}/refuse', [NursingMarController::class, 'refuse'])->middleware('can:nursing.mar.refuse')->name('mar.refuse');
    Route::post('mar/{mar}/omit', [NursingMarController::class, 'omit'])->middleware('can:nursing.mar.omit')->name('mar.omit');
    Route::post('mar/{mar}/correct', [NursingMarController::class, 'correct'])->middleware('can:nursing.mar.correct')->name('mar.correct');

    // Devices / IV / wounds / education
    Route::middleware(['can:nursing.device.view'])->group(function () {
        Route::get('episodes/{episode}/clinical', [NursingClinicalController::class, 'index'])->name('clinical.index');
    });
    Route::post('episodes/{episode}/devices', [NursingClinicalController::class, 'storeDevice'])->middleware('can:nursing.device.create')->name('devices.store');
    Route::post('devices/{device}/remove', [NursingClinicalController::class, 'removeDevice'])->middleware('can:nursing.device.update')->name('devices.remove');
    Route::post('episodes/{episode}/iv', [NursingClinicalController::class, 'storeIv'])->middleware('can:nursing.iv.create')->name('iv.store');
    Route::post('episodes/{episode}/wounds', [NursingClinicalController::class, 'storeWound'])->middleware('can:nursing.wound.create')->name('wounds.store');
    Route::post('episodes/{episode}/education', [NursingClinicalController::class, 'storeEducation'])->middleware('can:nursing.education.create')->name('education.store');

    // Notes
    Route::middleware(['can:nursing.notes.view'])->group(function () {
        Route::get('episodes/{episode}/notes', [NursingNoteController::class, 'index'])->name('notes.index');
    });
    Route::post('episodes/{episode}/notes', [NursingNoteController::class, 'store'])->middleware('can:nursing.notes.create')->name('notes.store');
    Route::put('notes/{note}', [NursingNoteController::class, 'update'])->middleware('can:nursing.notes.create')->name('notes.update');
    Route::post('notes/{note}/finalize', [NursingNoteController::class, 'finalize'])->middleware('can:nursing.notes.finalize')->name('notes.finalize');
    Route::post('notes/{note}/amend', [NursingNoteController::class, 'amend'])->middleware('can:nursing.notes.amend')->name('notes.amend');

    // Handover
    Route::middleware(['can:nursing.handover.view'])->group(function () {
        Route::get('handover', [NursingHandoverController::class, 'index'])->name('handover.index');
        Route::get('handover/{handover}', [NursingHandoverController::class, 'show'])->name('handover.show');
    });
    Route::post('episodes/{episode}/handover', [NursingHandoverController::class, 'store'])->middleware('can:nursing.handover.create')->name('handover.store');
    Route::post('handover/{handover}/finalize', [NursingHandoverController::class, 'finalize'])->middleware('can:nursing.handover.create')->name('handover.finalize');
    Route::post('handover/{handover}/acknowledge', [NursingHandoverController::class, 'acknowledge'])->middleware('can:nursing.handover.acknowledge')->name('handover.acknowledge');

    // Escalations
    Route::middleware(['can:nursing.escalation.view'])->group(function () {
        Route::get('escalations', [NursingEscalationController::class, 'index'])->name('escalations.index');
    });
    Route::post('episodes/{episode}/escalations', [NursingEscalationController::class, 'store'])->middleware('can:nursing.escalation.create')->name('escalations.store');
    Route::post('escalations/{escalation}/acknowledge', [NursingEscalationController::class, 'acknowledge'])->middleware('can:nursing.escalation.acknowledge')->name('escalations.acknowledge');
    Route::post('escalations/{escalation}/resolve', [NursingEscalationController::class, 'resolve'])->middleware('can:nursing.escalation.resolve')->name('escalations.resolve');

    // Discharge checklist
    Route::middleware(['can:nursing.discharge.complete'])->group(function () {
        Route::get('episodes/{episode}/discharge-checklist', [NursingDischargeChecklistController::class, 'show'])->name('discharge-checklist.show');
        Route::put('discharge-checklists/{checklist}', [NursingDischargeChecklistController::class, 'update'])->name('discharge-checklist.update');
        Route::post('discharge-checklists/{checklist}/complete', [NursingDischargeChecklistController::class, 'complete'])->name('discharge-checklist.complete');
    });

    // Reports
    Route::middleware(['can:nursing.reports.view'])->group(function () {
        Route::get('reports/workload', [NursingReportingController::class, 'workload'])->name('reports.workload');
        Route::get('reports/medication', [NursingReportingController::class, 'medication'])->name('reports.medication');
        Route::get('reports/quality', [NursingReportingController::class, 'quality'])->name('reports.quality');
    });

    // Settings
    Route::middleware(['can:nursing.settings.manage'])->group(function () {
        Route::get('settings', [NursingSettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [NursingSettingsController::class, 'update'])->name('settings.update');
    });
});
