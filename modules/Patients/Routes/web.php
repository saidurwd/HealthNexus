<?php

use Illuminate\Support\Facades\Route;
use Modules\Patients\Http\Controllers\PatientController;

Route::middleware('can:manage companies')->group(function () {
    Route::get('patients/search', [PatientController::class, 'search'])->name('patients.search');
    Route::post('patients/detect-duplicates', [PatientController::class, 'detectDuplicates'])->name('patients.detect-duplicates');
    Route::post('patients/merge', [PatientController::class, 'merge'])->name('patients.merge');
    Route::get('patients/{patient}/timeline', [PatientController::class, 'timeline'])->name('patients.timeline');
    Route::get('patients/{patient}/documents', [PatientController::class, 'documents'])->name('patients.documents');
    Route::post('patients/{patient}/documents', [PatientController::class, 'uploadDocument'])->name('patients.documents.upload');
    Route::delete('patients/{patient}/documents/{document}', [PatientController::class, 'deleteDocument'])->name('patients.documents.delete');
    Route::get('patients/{patient}/allergies', [PatientController::class, 'allergies'])->name('patients.allergies');
    Route::post('patients/{patient}/allergies', [PatientController::class, 'addAllergy'])->name('patients.allergies.add');
    Route::delete('patients/{patient}/allergies/{allergy}', [PatientController::class, 'deleteAllergy'])->name('patients.allergies.delete');
    Route::get('patients/{patient}/history', [PatientController::class, 'history'])->name('patients.history');
    Route::post('patients/{patient}/history', [PatientController::class, 'addHistory'])->name('patients.history.add');
    Route::delete('patients/{patient}/history/{history}', [PatientController::class, 'deleteHistory'])->name('patients.history.delete');

    Route::get('patients/{patient}/alerts', [PatientController::class, 'alerts'])->name('patients.alerts');
    Route::post('patients/{patient}/alerts', [PatientController::class, 'addAlert'])->name('patients.alerts.add');
    Route::put('patients/{patient}/alerts/{alert}/resolve', [PatientController::class, 'resolveAlert'])->name('patients.alerts.resolve');
    Route::delete('patients/{patient}/alerts/{alert}', [PatientController::class, 'deleteAlert'])->name('patients.alerts.delete');

    Route::resource('patients', PatientController::class);
});
