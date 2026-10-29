<?php

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Http\Controllers\Web\AnalyticsDashboardController;
use Modules\Analytics\Http\Controllers\Web\AnalyticsInsightController;
use Modules\Analytics\Http\Controllers\Web\AnalyticsReportController;

// BI and analytics: /admin/analytics/*.
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/', [AnalyticsDashboardController::class, 'index'])->name('index');
    Route::get('dashboards/new', [AnalyticsDashboardController::class, 'create'])->name('dashboards.create');
    Route::post('dashboards', [AnalyticsDashboardController::class, 'store'])->name('dashboards.store');
    Route::get('dashboards/{dashboard}', [AnalyticsDashboardController::class, 'show'])->name('dashboards.show');
    Route::post('dashboards/{dashboard}/delete', [AnalyticsDashboardController::class, 'destroy'])->name('dashboards.delete');

    Route::get('kpis', [AnalyticsDashboardController::class, 'kpis'])->name('kpis');
    Route::post('kpis', [AnalyticsDashboardController::class, 'saveKpi'])->name('kpis.save');
    Route::get('kpis/{kpi}', [AnalyticsDashboardController::class, 'kpi'])->name('kpis.show');
    Route::post('kpis/{kpi}/target', [AnalyticsDashboardController::class, 'setTarget'])->name('kpis.target');
    Route::post('kpis/{kpi}/retire', [AnalyticsDashboardController::class, 'retireKpi'])->name('kpis.retire');
    Route::get('explore', [AnalyticsDashboardController::class, 'explore'])->name('explore');

    Route::get('reports', [AnalyticsReportController::class, 'index'])->name('reports');
    Route::post('reports', [AnalyticsReportController::class, 'store'])->name('reports.store');
    Route::get('reports/{report}', [AnalyticsReportController::class, 'show'])->name('reports.show');
    Route::post('reports/{report}/delete', [AnalyticsReportController::class, 'destroy'])->name('reports.delete');
    Route::post('reports/{report}/schedule', [AnalyticsReportController::class, 'schedule'])->name('reports.schedule');
    Route::get('schedules', [AnalyticsReportController::class, 'schedules'])->name('schedules');
    Route::post('schedules/{schedule}/toggle', [AnalyticsReportController::class, 'toggleSchedule'])->name('schedules.toggle');

    Route::get('quality', [AnalyticsReportController::class, 'quality'])->name('quality');
    Route::post('quality/run', [AnalyticsReportController::class, 'runQuality'])->name('quality.run');
    Route::post('quality/{issue}/set-aside', [AnalyticsReportController::class, 'setAside'])->whereNumber('issue')->name('quality.aside');
    Route::get('loads', [AnalyticsReportController::class, 'loads'])->name('loads');
    Route::post('loads/run', [AnalyticsReportController::class, 'runLoads'])->name('loads.run');
    Route::post('loads/rebuild', [AnalyticsReportController::class, 'rebuild'])->name('loads.rebuild');

    Route::get('research', [AnalyticsInsightController::class, 'research'])->name('research');
    Route::post('research', [AnalyticsInsightController::class, 'requestDataset'])->name('research.request');
    Route::post('research/{dataset}', [AnalyticsInsightController::class, 'decideDataset'])->name('research.decide');
    Route::get('research/{dataset}/download', [AnalyticsInsightController::class, 'downloadDataset'])->name('research.download');
    Route::get('benchmark', [AnalyticsInsightController::class, 'benchmark'])->name('benchmark');
    Route::post('benchmark/opt-in', [AnalyticsInsightController::class, 'optIn'])->name('benchmark.optin');
    Route::get('national', [AnalyticsInsightController::class, 'national'])->name('national');
    Route::post('national', [AnalyticsInsightController::class, 'saveNational'])->name('national.save');
    Route::get('forecast', [AnalyticsInsightController::class, 'forecast'])->name('forecast');
    Route::post('forecast', [AnalyticsInsightController::class, 'makeForecast'])->name('forecast.make');
    Route::get('ask', [AnalyticsInsightController::class, 'ask'])->name('ask');
});
