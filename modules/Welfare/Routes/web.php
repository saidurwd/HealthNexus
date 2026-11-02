<?php

use Illuminate\Support\Facades\Route;
use Modules\Welfare\Http\Controllers\Web\AssessmentController;
use Modules\Welfare\Http\Controllers\Web\FundController;
use Modules\Welfare\Http\Controllers\Web\GrantController;
use Modules\Welfare\Http\Controllers\Web\WelfareHomeController;

// Patient welfare: /admin/welfare/*. Every screen checks its own right; income and circumstances are only for those who hold the assessment right.
Route::prefix('welfare')->name('welfare.')->group(function () {
    Route::get('/', [WelfareHomeController::class, 'index'])->name('index');
    Route::get('reports', [WelfareHomeController::class, 'reports'])->name('reports');

    Route::get('funds', [FundController::class, 'index'])->name('funds');
    Route::post('funds', [FundController::class, 'store'])->name('funds.store');
    Route::get('funds/{fund}', [FundController::class, 'show'])->name('funds.show');
    Route::post('funds/{fund}/donations', [FundController::class, 'donate'])->name('funds.donate');
    Route::post('funds/{fund}/close', [FundController::class, 'close'])->name('funds.close');
    Route::post('entries/{entry}/reverse', [FundController::class, 'reverse'])->name('entries.reverse');

    Route::get('assessments', [AssessmentController::class, 'index'])->name('assessments');
    Route::post('assessments', [AssessmentController::class, 'store'])->name('assessments.store');
    Route::get('assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show');
    Route::post('assessments/{assessment}/submit', [AssessmentController::class, 'submit'])->name('assessments.submit');
    Route::post('assessments/{assessment}/decide', [AssessmentController::class, 'decide'])->name('assessments.decide');

    Route::get('grants', [GrantController::class, 'index'])->name('grants');
    Route::post('assessments/{assessment}/grants', [GrantController::class, 'store'])->name('grants.store');
    Route::get('grants/{grant}', [GrantController::class, 'show'])->name('grants.show');
    Route::post('grants/{grant}/approve', [GrantController::class, 'approve'])->name('grants.approve');
    Route::post('grants/{grant}/reject', [GrantController::class, 'reject'])->name('grants.reject');
    Route::post('grants/{grant}/cancel', [GrantController::class, 'cancel'])->name('grants.cancel');
    Route::post('grants/{grant}/apply', [GrantController::class, 'apply'])->name('grants.apply');
});
