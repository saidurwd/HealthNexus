<?php

use App\Http\Middleware\FhirAuthenticate;
use Illuminate\Support\Facades\Route;
use Modules\Interop\Http\Controllers\Api\DicomController;
use Modules\Interop\Http\Controllers\Api\FhirController;
use Modules\Interop\Http\Controllers\Api\WebhookController;

// FHIR R4: /api/v1/fhir/*. Client tokens (partners) or staff logins; every error is an OperationOutcome.
Route::prefix('fhir')->name('api.fhir.')->group(function () {
    Route::get('metadata', [FhirController::class, 'metadata'])->name('metadata');

    Route::middleware([FhirAuthenticate::class.':read', 'throttle:240,1'])->group(function () {
        Route::get('CodeSystem/$lookup', [FhirController::class, 'lookup'])->name('lookup');
        Route::get('ConceptMap/$translate', [FhirController::class, 'translate'])->name('translate');
        Route::post('$validate', [FhirController::class, 'validateResource'])->name('validate');
        Route::post('{type}/$validate', [FhirController::class, 'validateResource'])->name('validate.type');
        Route::get('{type}/{id}/$everything', [FhirController::class, 'everything'])->name('everything');
        Route::get('{type}', [FhirController::class, 'search'])->name('search');
        Route::get('{type}/{id}', [FhirController::class, 'read'])->name('read');
    });

    Route::middleware([FhirAuthenticate::class.':write', 'throttle:60,1'])->group(function () {
        Route::post('{type}', [FhirController::class, 'create'])->name('create');
        Route::put('{type}/{id}', [FhirController::class, 'update'])->name('update');
    });
});

// DICOMweb-style: /api/v1/dicom/* for scanners (modality worklist) and viewers (study lookup).
Route::prefix('dicom')->name('api.dicom.')->middleware([FhirAuthenticate::class.':dicom', 'throttle:240,1'])->group(function () {
    Route::get('worklist', [DicomController::class, 'worklist'])->name('worklist');
    Route::put('worklist/{accession}/status', [DicomController::class, 'status'])->name('status');
    Route::get('studies', [DicomController::class, 'studies'])->name('studies');
});

// Signed notices from partners. No login: the signature is the credential.
Route::post('interoperability/webhooks/{partner}', [WebhookController::class, 'receive'])->middleware('throttle:120,1')->name('api.interop.webhook');
