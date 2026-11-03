<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\Api\FinanceReportApiController;

// Read-only: /api/v1/finance/*  (staff tokens; each endpoint checks its own permission)
Route::prefix('finance')->name('api.finance.')->middleware('throttle:60,1')->group(function () {
    Route::get('trial-balance', [FinanceReportApiController::class, 'trialBalance'])->name('trial-balance');
    Route::get('income-statement', [FinanceReportApiController::class, 'incomeStatement'])->name('income-statement');
    Route::get('balance-sheet', [FinanceReportApiController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('receivables', [FinanceReportApiController::class, 'receivables'])->name('receivables');
    Route::get('payables', [FinanceReportApiController::class, 'payables'])->name('payables');
    Route::get('reconciliation', [FinanceReportApiController::class, 'reconciliation'])->name('reconciliation');
});
