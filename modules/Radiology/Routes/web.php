<?php

use Illuminate\Support\Facades\Route;
use Modules\Radiology\Http\Controllers\Web\RadiologyCriticalFindingController;
use Modules\Radiology\Http\Controllers\Web\RadiologyDashboardController;
use Modules\Radiology\Http\Controllers\Web\RadiologyExaminationController;
use Modules\Radiology\Http\Controllers\Web\RadiologyModalityController;
use Modules\Radiology\Http\Controllers\Web\RadiologyOrderController;
use Modules\Radiology\Http\Controllers\Web\RadiologyPacsController;
use Modules\Radiology\Http\Controllers\Web\RadiologyProcedureController;
use Modules\Radiology\Http\Controllers\Web\RadiologyReportController;
use Modules\Radiology\Http\Controllers\Web\RadiologyReportingController;
use Modules\Radiology\Http\Controllers\Web\RadiologySchedulingController;
use Modules\Radiology\Http\Controllers\Web\RadiologySettingsController;
use Modules\Radiology\Http\Controllers\Web\RadiologyStudyController;
use Modules\Radiology\Http\Controllers\Web\RadiologyWorklistController;

Route::middleware(['can:radiology.dashboard.view'])->group(function () {
    Route::get('radiology', [RadiologyDashboardController::class, 'index'])->name('radiology.dashboard');
});

Route::prefix('radiology')->name('radiology.')->group(function () {
    // Procedures / Modalities
    Route::middleware(['can:radiology.procedure.view'])->group(function () {
        Route::get('procedures', [RadiologyProcedureController::class, 'index'])->name('procedures.index');
    });

    Route::middleware(['can:radiology.procedure.create'])->group(function () {
        Route::get('procedures/create', [RadiologyProcedureController::class, 'create'])->name('procedures.create');
        Route::post('procedures', [RadiologyProcedureController::class, 'store'])->name('procedures.store');
    });

    Route::middleware(['can:radiology.procedure.update'])->group(function () {
        Route::get('procedures/{procedure}/edit', [RadiologyProcedureController::class, 'edit'])->name('procedures.edit');
        Route::put('procedures/{procedure}', [RadiologyProcedureController::class, 'update'])->name('procedures.update');
        Route::delete('procedures/{procedure}', [RadiologyProcedureController::class, 'destroy'])->name('procedures.destroy');
    });

    Route::middleware(['can:radiology.modality.view'])->group(function () {
        Route::get('modalities', [RadiologyModalityController::class, 'index'])->name('modalities.index');
    });

    Route::middleware(['can:radiology.modality.create'])->group(function () {
        Route::get('modalities/create', [RadiologyModalityController::class, 'create'])->name('modalities.create');
        Route::post('modalities', [RadiologyModalityController::class, 'store'])->name('modalities.store');
    });

    Route::middleware(['can:radiology.modality.update'])->group(function () {
        Route::get('modalities/{modality}/edit', [RadiologyModalityController::class, 'edit'])->name('modalities.edit');
        Route::put('modalities/{modality}', [RadiologyModalityController::class, 'update'])->name('modalities.update');
    });

    // Orders
    Route::middleware(['can:radiology.order.view'])->group(function () {
        Route::get('orders', [RadiologyOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [RadiologyOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/resolve-item', [RadiologyOrderController::class, 'resolveUnmatchedItem'])->name('orders.resolve-item');
    });

    Route::middleware(['can:radiology.order.cancel'])->group(function () {
        Route::post('orders/{order}/cancel', [RadiologyOrderController::class, 'cancel'])->name('orders.cancel');
    });

    // Scheduling
    Route::middleware(['can:radiology.schedule.view'])->group(function () {
        Route::get('order-items/{item}/schedule', [RadiologySchedulingController::class, 'create'])->name('scheduling.create');
    });

    Route::middleware(['can:radiology.schedule.create'])->group(function () {
        Route::post('order-items/{item}/schedule', [RadiologySchedulingController::class, 'store'])->name('scheduling.store');
    });

    // Examinations
    Route::middleware(['can:radiology.examination.view'])->group(function () {
        Route::get('examinations', [RadiologyExaminationController::class, 'index'])->name('examinations.index');
        Route::get('examinations/{examination}', [RadiologyExaminationController::class, 'show'])->name('examinations.show');
    });

    Route::middleware(['can:radiology.examination.start'])->group(function () {
        Route::post('examinations/{examination}/check-in', [RadiologyExaminationController::class, 'checkIn'])->name('examinations.check-in');
        Route::post('examinations/{examination}/start-preparation', [RadiologyExaminationController::class, 'startPreparation'])->name('examinations.start-preparation');
        Route::post('examinations/{examination}/start', [RadiologyExaminationController::class, 'start'])->name('examinations.start');
    });

    Route::middleware(['can:radiology.examination.complete'])->group(function () {
        Route::post('examinations/{examination}/complete', [RadiologyExaminationController::class, 'complete'])->name('examinations.complete');
    });

    // Studies
    Route::middleware(['can:radiology.study.view'])->group(function () {
        Route::get('studies', [RadiologyStudyController::class, 'index'])->name('studies.index');
        Route::get('studies/{study}', [RadiologyStudyController::class, 'show'])->name('studies.show');
        Route::get('studies/{study}/viewer', [RadiologyStudyController::class, 'viewer'])->name('studies.viewer');
        Route::post('studies/{study}/sync', [RadiologyStudyController::class, 'sync'])->name('studies.sync');
    });

    // Worklist
    Route::middleware(['can:radiology.worklist.view'])->group(function () {
        Route::get('worklist', [RadiologyWorklistController::class, 'index'])->name('worklist.index');
    });

    Route::middleware(['can:radiology.worklist.assign'])->group(function () {
        Route::post('worklist/{examination}/assign', [RadiologyWorklistController::class, 'assign'])->name('worklist.assign');
    });

    // Reports
    Route::middleware(['can:radiology.report.view'])->group(function () {
        Route::get('reports', [RadiologyReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [RadiologyReportController::class, 'show'])->name('reports.show');
    });

    Route::middleware(['can:radiology.report.create'])->group(function () {
        Route::get('examinations/{examination}/report/create', [RadiologyReportController::class, 'create'])->name('reports.create');
        Route::post('examinations/{examination}/report', [RadiologyReportController::class, 'store'])->name('reports.store');
    });

    Route::middleware(['can:radiology.report.update'])->group(function () {
        Route::put('reports/{report}', [RadiologyReportController::class, 'update'])->name('reports.update');
    });

    Route::middleware(['can:radiology.report.submit'])->group(function () {
        Route::post('reports/{report}/submit', [RadiologyReportController::class, 'submit'])->name('reports.submit');
    });

    Route::middleware(['can:radiology.report.approve'])->group(function () {
        Route::post('reports/{report}/approve', [RadiologyReportController::class, 'approve'])->name('reports.approve');
    });

    Route::middleware(['can:radiology.report.amend'])->group(function () {
        Route::post('reports/{report}/amend', [RadiologyReportController::class, 'amend'])->name('reports.amend');
    });

    // Critical findings
    Route::middleware(['can:radiology.critical_finding.view'])->group(function () {
        Route::get('critical-findings', [RadiologyCriticalFindingController::class, 'index'])->name('critical-findings.index');
    });

    Route::middleware(['can:radiology.critical_finding.create'])->group(function () {
        Route::post('reports/{report}/critical-findings', [RadiologyCriticalFindingController::class, 'store'])->name('critical-findings.store');
    });

    Route::middleware(['can:radiology.critical_finding.acknowledge'])->group(function () {
        Route::post('critical-findings/{finding}/acknowledge', [RadiologyCriticalFindingController::class, 'acknowledge'])->name('critical-findings.acknowledge');
    });

    // Analytics
    Route::middleware(['can:radiology.report.view'])->group(function () {
        Route::get('analytics/daily-volume', [RadiologyReportingController::class, 'dailyVolume'])->name('analytics.daily-volume');
        Route::get('analytics/modality-utilization', [RadiologyReportingController::class, 'modalityUtilization'])->name('analytics.modality-utilization');
        Route::get('analytics/turnaround-time', [RadiologyReportingController::class, 'turnaroundTime'])->name('analytics.turnaround-time');
        Route::get('analytics/critical-findings', [RadiologyReportingController::class, 'criticalFindings'])->name('analytics.critical-findings');
    });

    // PACS
    Route::middleware(['can:radiology.pacs.view'])->group(function () {
        Route::get('pacs', [RadiologyPacsController::class, 'index'])->name('pacs.index');
    });

    Route::middleware(['can:radiology.pacs.manage'])->group(function () {
        Route::get('pacs/create', [RadiologyPacsController::class, 'create'])->name('pacs.create');
        Route::post('pacs', [RadiologyPacsController::class, 'store'])->name('pacs.store');
    });

    // Settings
    Route::middleware(['can:radiology.settings.manage'])->group(function () {
        Route::get('settings', [RadiologySettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [RadiologySettingsController::class, 'update'])->name('settings.update');
    });
});
