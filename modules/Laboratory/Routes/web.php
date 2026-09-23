<?php

use Illuminate\Support\Facades\Route;
use Modules\Laboratory\Http\Controllers\Web\LabAnalyzerController;
use Modules\Laboratory\Http\Controllers\Web\LabCriticalResultController;
use Modules\Laboratory\Http\Controllers\Web\LabDashboardController;
use Modules\Laboratory\Http\Controllers\Web\LabOrderController;
use Modules\Laboratory\Http\Controllers\Web\LabPanelController;
use Modules\Laboratory\Http\Controllers\Web\LabQcController;
use Modules\Laboratory\Http\Controllers\Web\LabReportController;
use Modules\Laboratory\Http\Controllers\Web\LabReportingController;
use Modules\Laboratory\Http\Controllers\Web\LabResultController;
use Modules\Laboratory\Http\Controllers\Web\LabSettingsController;
use Modules\Laboratory\Http\Controllers\Web\LabSpecimenController;
use Modules\Laboratory\Http\Controllers\Web\LabTestController;
use Modules\Laboratory\Http\Controllers\Web\LabWorklistController;

Route::middleware(['can:lab.dashboard.view'])->group(function () {
    Route::get('lab', [LabDashboardController::class, 'index'])->name('lab.dashboard');
});

Route::prefix('lab')->name('lab.')->group(function () {
    // Test catalog
    Route::middleware(['can:lab.test.view'])->group(function () {
        Route::get('tests', [LabTestController::class, 'index'])->name('tests.index');
        Route::get('panels', [LabPanelController::class, 'index'])->name('panels.index');
    });

    Route::middleware(['can:lab.test.create'])->group(function () {
        Route::get('tests/create', [LabTestController::class, 'create'])->name('tests.create');
        Route::post('tests', [LabTestController::class, 'store'])->name('tests.store');
        Route::get('panels/create', [LabPanelController::class, 'create'])->name('panels.create');
        Route::post('panels', [LabPanelController::class, 'store'])->name('panels.store');
    });

    Route::middleware(['can:lab.test.update'])->group(function () {
        Route::get('tests/{test}/edit', [LabTestController::class, 'edit'])->name('tests.edit');
        Route::put('tests/{test}', [LabTestController::class, 'update'])->name('tests.update');
        Route::delete('tests/{test}', [LabTestController::class, 'destroy'])->name('tests.destroy');
        Route::get('panels/{panel}/edit', [LabPanelController::class, 'edit'])->name('panels.edit');
        Route::put('panels/{panel}', [LabPanelController::class, 'update'])->name('panels.update');
    });

    // Orders
    Route::middleware(['can:lab.order.view'])->group(function () {
        Route::get('orders', [LabOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [LabOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/resolve-item', [LabOrderController::class, 'resolveUnmatchedItem'])->name('orders.resolve-item');
    });

    Route::middleware(['can:lab.order.cancel'])->group(function () {
        Route::post('orders/{order}/cancel', [LabOrderController::class, 'cancel'])->name('orders.cancel');
    });

    // Specimens
    Route::middleware(['can:lab.specimen.view'])->group(function () {
        Route::get('specimens', [LabSpecimenController::class, 'index'])->name('specimens.index');
        Route::get('specimens/{specimen}', [LabSpecimenController::class, 'show'])->name('specimens.show');
    });

    Route::middleware(['can:lab.specimen.collect'])->group(function () {
        Route::post('orders/{order}/specimens', [LabSpecimenController::class, 'collect'])->name('specimens.collect');
    });

    Route::middleware(['can:lab.specimen.receive'])->group(function () {
        Route::post('specimens/{specimen}/receive', [LabSpecimenController::class, 'receive'])->name('specimens.receive');
    });

    Route::middleware(['can:lab.specimen.reject'])->group(function () {
        Route::post('specimens/{specimen}/reject', [LabSpecimenController::class, 'reject'])->name('specimens.reject');
    });

    // Worklist
    Route::middleware(['can:lab.result.view'])->group(function () {
        Route::get('worklist', [LabWorklistController::class, 'index'])->name('worklist.index');
    });

    // Results
    Route::middleware(['can:lab.result.create'])->group(function () {
        Route::post('order-items/{item}/results', [LabResultController::class, 'store'])->name('results.store');
    });

    Route::middleware(['can:lab.result.validate'])->group(function () {
        Route::post('results/{result}/technical-validate', [LabResultController::class, 'validateTechnical'])->name('results.technical-validate');
    });

    Route::middleware(['can:lab.result.approve'])->group(function () {
        Route::post('results/{result}/approve', [LabResultController::class, 'approve'])->name('results.approve');
    });

    Route::middleware(['can:lab.result.amend'])->group(function () {
        Route::post('results/{result}/amend', [LabResultController::class, 'amend'])->name('results.amend');
    });

    // Critical results
    Route::middleware(['can:lab.critical_result.view'])->group(function () {
        Route::get('critical-results', [LabCriticalResultController::class, 'index'])->name('critical-results.index');
    });

    Route::middleware(['can:lab.critical_result.acknowledge'])->group(function () {
        Route::post('critical-results/{alert}/acknowledge', [LabCriticalResultController::class, 'acknowledge'])->name('critical-results.acknowledge');
    });

    // Reports
    Route::middleware(['can:lab.report.view'])->group(function () {
        Route::get('reports', [LabReportController::class, 'index'])->name('reports.index');
        Route::get('reports-analytics/daily-volume', [LabReportingController::class, 'dailyVolume'])->name('reports.daily-volume');
        Route::get('reports-analytics/sample-rejection', [LabReportingController::class, 'sampleRejection'])->name('reports.sample-rejection');
        Route::get('reports-analytics/critical-results', [LabReportingController::class, 'criticalResults'])->name('reports.critical-results');
        Route::get('reports-analytics/result-amendments', [LabReportingController::class, 'resultAmendments'])->name('reports.result-amendments');
        Route::get('reports/{report}/print', [LabReportController::class, 'print'])->name('reports.print');
        Route::get('reports/{report}', [LabReportController::class, 'show'])->name('reports.show');
    });

    Route::middleware(['can:lab.report.generate'])->group(function () {
        Route::post('orders/{order}/report', [LabReportController::class, 'finalize'])->name('reports.finalize');
    });

    // Analyzers
    Route::middleware(['can:lab.analyzer.view'])->group(function () {
        Route::get('analyzers', [LabAnalyzerController::class, 'index'])->name('analyzers.index');
    });

    Route::middleware(['can:lab.analyzer.configure'])->group(function () {
        Route::get('analyzers/create', [LabAnalyzerController::class, 'create'])->name('analyzers.create');
        Route::post('analyzers', [LabAnalyzerController::class, 'store'])->name('analyzers.store');
    });

    // Quality control
    Route::middleware(['can:lab.qc.view'])->group(function () {
        Route::get('qc', [LabQcController::class, 'index'])->name('qc.index');
    });

    Route::middleware(['can:lab.qc.create'])->group(function () {
        Route::get('qc/create', [LabQcController::class, 'create'])->name('qc.create');
        Route::post('qc', [LabQcController::class, 'store'])->name('qc.store');
    });

    // Settings
    Route::middleware(['can:lab.settings.manage'])->group(function () {
        Route::get('settings', [LabSettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [LabSettingsController::class, 'update'])->name('settings.update');
    });
});
