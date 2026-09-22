<?php

use Illuminate\Support\Facades\Route;
use Modules\Clinical\Http\Controllers\EncounterController;
use Modules\Clinical\Http\Controllers\EncounterClinicalController;

Route::middleware('can:manage companies')->group(function () {
    Route::resource('encounters', EncounterController::class);

    Route::post('encounters/{encounter}/start', [EncounterClinicalController::class, 'start'])->name('encounters.start');
    Route::post('encounters/{encounter}/pause', [EncounterClinicalController::class, 'pause'])->name('encounters.pause');
    Route::post('encounters/{encounter}/resume', [EncounterClinicalController::class, 'resume'])->name('encounters.resume');
    Route::post('encounters/{encounter}/complete', [EncounterClinicalController::class, 'complete'])->name('encounters.complete');
    Route::post('encounters/{encounter}/lock', [EncounterClinicalController::class, 'lock'])->name('encounters.lock');

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
});
