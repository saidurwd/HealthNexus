<?php

use Illuminate\Support\Facades\Route;
use Modules\Patients\Http\Controllers\PatientController;

// Route-level gates mirror the plural patients.* permission convention enforced by PatientPolicy
// (index/show-type routes need patients.view, mutations need the matching action permission).
// Previously the whole module sat behind a single blanket 'can:manage companies' gate, which
// silently locked doctor/nurse/receptionist out of every patient route despite them holding the
// documented patients.* grants in PermissionSeeder.
//
// Ordering note: every literal top-level path (patients/create, patients/search, ...) is
// registered before the `patients/{patient}` wildcard show route below, regardless of which
// permission group it belongs to — Laravel matches routes in registration order, so a wildcard
// registered first would swallow "create"/"search"/etc. as the {patient} route-model-binding key
// and 404 (this exact bug was hit and fixed in the Billing module earlier in this project).

Route::middleware('can:patients.create')->group(function () {
    Route::get('patients/create', [PatientController::class, 'create'])->name('patients.create');
});

Route::middleware('can:patients.view')->group(function () {
    Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('patients/search', [PatientController::class, 'search'])->name('patients.search');
    Route::get('patients/duplicates', [PatientController::class, 'duplicates'])->name('patients.duplicates');
    Route::get('patients/amendments', [PatientController::class, 'amendments'])->name('patients.amendments');
});

Route::middleware('can:patients.create')->group(function () {
    Route::post('patients', [PatientController::class, 'store'])->name('patients.store');
});

Route::middleware('can:patients.merge')->group(function () {
    Route::post('patients/detect-duplicates', [PatientController::class, 'detectDuplicates'])->name('patients.detect-duplicates');
    Route::post('patients/merge', [PatientController::class, 'merge'])->name('patients.merge');
    Route::put('patients/duplicates/{candidate}/review', [PatientController::class, 'reviewDuplicate'])->name('patients.duplicates.review');
});

Route::middleware('can:patients.amend.approve')->group(function () {
    Route::put('patients/amendments/{amendment}/approve', [PatientController::class, 'approveAmendment'])->name('patients.amendments.approve');
    Route::put('patients/amendments/{amendment}/reject', [PatientController::class, 'rejectAmendment'])->name('patients.amendments.reject');
});

// --- Everything below this point touches a specific {patient} and may safely use the wildcard ---

Route::middleware('can:patients.view')->group(function () {
    Route::get('patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('patients/{patient}/timeline', [PatientController::class, 'timeline'])->name('patients.timeline');
    Route::get('patients/{patient}/allergies', [PatientController::class, 'allergies'])->name('patients.allergies');
    Route::get('patients/{patient}/history', [PatientController::class, 'history'])->name('patients.history');
    Route::get('patients/{patient}/alerts', [PatientController::class, 'alerts'])->name('patients.alerts');
    Route::get('patients/{patient}/fhir', [PatientController::class, 'fhir'])->name('patients.fhir');
    Route::get('patients/{patient}/addresses', [PatientController::class, 'addresses'])->name('patients.addresses');
    Route::get('patients/{patient}/guardians', [PatientController::class, 'guardians'])->name('patients.guardians');
    Route::get('patients/{patient}/audit', [PatientController::class, 'auditTrail'])->name('patients.audit');
    Route::get('patients/{patient}/appointments', [PatientController::class, 'appointments'])->name('patients.appointments');
});

Route::middleware('can:patients.update')->group(function () {
    Route::get('patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::put('patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::post('patients/{patient}/addresses', [PatientController::class, 'addAddress'])->name('patients.addresses.add');
    Route::post('patients/{patient}/guardians', [PatientController::class, 'addGuardian'])->name('patients.guardians.add');
    Route::put('patients/{patient}/preferences', [PatientController::class, 'setPreferences'])->name('patients.preferences.set');
    Route::post('patients/{patient}/photo', [PatientController::class, 'uploadPhoto'])->name('patients.photo.upload');
    Route::post('patients/{patient}/allergies', [PatientController::class, 'addAllergy'])->name('patients.allergies.add');
    Route::delete('patients/{patient}/allergies/{allergy}', [PatientController::class, 'deleteAllergy'])->name('patients.allergies.delete');
    Route::post('patients/{patient}/history', [PatientController::class, 'addHistory'])->name('patients.history.add');
    Route::delete('patients/{patient}/history/{history}', [PatientController::class, 'deleteHistory'])->name('patients.history.delete');
    Route::post('patients/{patient}/alerts', [PatientController::class, 'addAlert'])->name('patients.alerts.add');
    Route::put('patients/{patient}/alerts/{alert}/resolve', [PatientController::class, 'resolveAlert'])->name('patients.alerts.resolve');
    Route::delete('patients/{patient}/alerts/{alert}', [PatientController::class, 'deleteAlert'])->name('patients.alerts.delete');
});

Route::middleware('can:patients.delete')->group(function () {
    Route::delete('patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
});

Route::middleware('can:patients.documents.view')->group(function () {
    Route::get('patients/{patient}/documents', [PatientController::class, 'documents'])->name('patients.documents');
    Route::get('patients/{patient}/documents/{document}/download', [PatientController::class, 'downloadDocument'])->name('patients.documents.download');
});

Route::middleware('can:patients.documents.manage')->group(function () {
    Route::post('patients/{patient}/documents', [PatientController::class, 'uploadDocument'])->name('patients.documents.upload');
    Route::delete('patients/{patient}/documents/{document}', [PatientController::class, 'deleteDocument'])->name('patients.documents.delete');
});

Route::middleware('can:patients.consents.view')->group(function () {
    Route::get('patients/{patient}/consents', [PatientController::class, 'consents'])->name('patients.consents');
});

Route::middleware('can:patients.consents.manage')->group(function () {
    Route::post('patients/{patient}/consents', [PatientController::class, 'grantConsent'])->name('patients.consents.grant');
    Route::put('patients/{patient}/consents/{consent}/withdraw', [PatientController::class, 'withdrawConsent'])->name('patients.consents.withdraw');
});

Route::middleware('can:patients.amend.request')->group(function () {
    Route::post('patients/{patient}/amendments', [PatientController::class, 'requestAmendment'])->name('patients.amendments.request');
});

Route::middleware('can:patients.print')->group(function () {
    Route::get('patients/{patient}/print', [PatientController::class, 'print'])->name('patients.print');
    Route::get('patients/{patient}/barcode', [PatientController::class, 'barcode'])->name('patients.barcode');
});

Route::middleware('can:patients.portal.manage')->group(function () {
    Route::post('patients/{patient}/portal', [PatientController::class, 'enablePortal'])->name('patients.portal.enable');
});
