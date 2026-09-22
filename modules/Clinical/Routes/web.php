<?php

use Illuminate\Support\Facades\Route;
use Modules\Clinical\Http\Controllers\EncounterController;
use Modules\Clinical\Http\Controllers\EncounterClinicalController;
use Modules\Clinical\Http\Controllers\BreakGlassController;
use Modules\Clinical\Http\Controllers\ClinicalReportController;

Route::middleware(['can:encounters.view'])->group(function () {
    Route::get('encounters', [EncounterController::class, 'index'])->name('encounters.index');
    Route::get('encounters/{encounter}', [EncounterController::class, 'show'])->name('encounters.show');
});

Route::middleware(['can:encounters.create'])->group(function () {
    Route::get('encounters/create', [EncounterController::class, 'create'])->name('encounters.create');
    Route::post('encounters', [EncounterController::class, 'store'])->name('encounters.store');
});

Route::middleware(['can:encounters.update'])->group(function () {
    Route::get('encounters/{encounter}/edit', [EncounterController::class, 'edit'])->name('encounters.edit');
    Route::put('encounters/{encounter}', [EncounterController::class, 'update'])->name('encounters.update');
});

Route::middleware(['can:encounters.delete'])->group(function () {
    Route::delete('encounters/{encounter}', [EncounterController::class, 'destroy'])->name('encounters.destroy');
});

Route::middleware(['can:encounter.start'])->group(function () {
    Route::post('encounters/{encounter}/start', [EncounterClinicalController::class, 'start'])->name('encounters.start');
});

Route::middleware(['can:encounter.complete'])->group(function () {
    Route::post('encounters/{encounter}/complete', [EncounterClinicalController::class, 'complete'])->name('encounters.complete');
});

Route::middleware(['can:encounter.cancel'])->group(function () {
    Route::post('encounters/{encounter}/cancel', [EncounterClinicalController::class, 'cancel'])->name('encounters.cancel');
});

Route::middleware(['can:encounter.lock'])->group(function () {
    Route::post('encounters/{encounter}/lock', [EncounterClinicalController::class, 'lock'])->name('encounters.lock');
});

Route::middleware(['can:encounters.update'])->group(function () {
    Route::post('encounters/{encounter}/pause', [EncounterClinicalController::class, 'pause'])->name('encounters.pause');
    Route::post('encounters/{encounter}/resume', [EncounterClinicalController::class, 'resume'])->name('encounters.resume');
    Route::post('encounters/{encounter}/transfer', [EncounterClinicalController::class, 'transfer'])->name('encounters.transfer');
    Route::post('encounters/{encounter}/complaints', [EncounterClinicalController::class, 'storeComplaint'])->name('encounters.complaints.store');
    Route::post('encounters/{encounter}/histories', [EncounterClinicalController::class, 'storeHistory'])->name('encounters.histories.store');
    Route::post('encounters/{encounter}/examinations', [EncounterClinicalController::class, 'storeExamination'])->name('encounters.examinations.store');
    Route::post('encounters/{encounter}/review-of-systems', [EncounterClinicalController::class, 'storeReviewOfSystem'])->name('encounters.review-of-systems.store');
    Route::post('encounters/{encounter}/problems', [EncounterClinicalController::class, 'storeProblem'])->name('encounters.problems.store');
    Route::post('encounters/{encounter}/procedures', [EncounterClinicalController::class, 'storeProcedure'])->name('encounters.procedures.store');
    Route::post('encounters/{encounter}/orders', [EncounterClinicalController::class, 'storeOrder'])->name('encounters.orders.store');
    Route::post('encounters/{encounter}/referrals', [EncounterClinicalController::class, 'storeReferral'])->name('encounters.referrals.store');
    Route::post('encounters/{encounter}/instructions', [EncounterClinicalController::class, 'storeInstruction'])->name('encounters.instructions.store');
    Route::post('encounters/{encounter}/notes', [EncounterClinicalController::class, 'storeNote'])->name('encounters.notes.store');
    Route::post('encounters/{encounter}/documents', [EncounterClinicalController::class, 'storeDocument'])->name('encounters.documents.store');
    Route::post('encounters/{encounter}/amendments', [EncounterClinicalController::class, 'storeAmendment'])->name('encounters.amendments.store');
    Route::post('encounters/{encounter}/prescriptions/{prescription}/issue', [EncounterClinicalController::class, 'issuePrescription'])->name('encounters.prescriptions.issue');
    Route::post('encounters/{encounter}/prescriptions/{prescription}/cancel', [EncounterClinicalController::class, 'cancelPrescription'])->name('encounters.prescriptions.cancel');
});

Route::middleware(['can:clinical.break_glass'])->group(function () {
    Route::post('encounters/{encounter}/break-glass', [BreakGlassController::class, 'request'])->name('encounters.break-glass');
});

Route::middleware(['can:encounter.export'])->group(function () {
    Route::get('reports/clinical', [ClinicalReportController::class, 'index'])->name('reports.clinical');
});