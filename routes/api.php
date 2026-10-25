<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CodeSearchController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EncounterController;
use App\Http\Controllers\Api\V1\GlobalSearchController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\ProviderController;
use App\Http\Controllers\Api\V1\ReportExportController;
use App\Http\Controllers\Api\V1\TenantContextController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Middleware\ApplyTenantContextToInput;
use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureOrganizationAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum', ApplyTenantContextToInput::class])->group(function () {
        Route::get('/tenant/context', [TenantContextController::class, 'show']);
        Route::get('/codes/search', CodeSearchController::class);
        Route::get('/search', [GlobalSearchController::class, 'search']);
        Route::get('/scan', [GlobalSearchController::class, 'scan']);
        Route::post('/exports', [ReportExportController::class, 'store']);
        Route::get('/exports/{export}', [ReportExportController::class, 'show']);
        Route::get('/exports/{export}/download', [ReportExportController::class, 'download']);
        Route::post('/tenant/context', [TenantContextController::class, 'update']);

        require __DIR__.'/../modules/Settings/Routes/api.php';
        require __DIR__.'/../modules/Notifications/Routes/api.php';
        Route::middleware('module:radiology')->group(fn () => require __DIR__.'/../modules/Radiology/Routes/api.php');
        Route::middleware('module:pharmacy')->group(fn () => require __DIR__.'/../modules/Pharmacy/Routes/api.php');
        Route::middleware('module:ipd')->group(fn () => require __DIR__.'/../modules/Ipd/Routes/api.php');
        Route::middleware('module:nursing')->group(fn () => require __DIR__.'/../modules/Nursing/Routes/api.php');
        Route::middleware('module:ot')->group(fn () => require __DIR__.'/../modules/Ot/Routes/api.php');
        Route::middleware('module:icu')->group(fn () => require __DIR__.'/../modules/Icu/Routes/api.php');

        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::post('/organizations', [OrganizationController::class, 'store']);
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->middleware(EnsureOrganizationAccess::class);
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->middleware(EnsureOrganizationAccess::class);
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->middleware(EnsureOrganizationAccess::class);

        Route::get('/organizations/{organization}/branches', [BranchController::class, 'index'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/organizations/{organization}/branches', [BranchController::class, 'store'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/organizations/{organization}/branches/{branch}', [BranchController::class, 'show'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::put('/organizations/{organization}/branches/{branch}', [BranchController::class, 'update'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::delete('/organizations/{organization}/branches/{branch}', [BranchController::class, 'destroy'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/organizations/{organization}/branches/{branch}/departments', [DepartmentController::class, 'index'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/organizations/{organization}/branches/{branch}/departments', [DepartmentController::class, 'store'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::get('/organizations/{organization}/branches/{branch}/departments/{department}', [DepartmentController::class, 'show'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::put('/organizations/{organization}/branches/{branch}/departments/{department}', [DepartmentController::class, 'update'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::delete('/organizations/{organization}/branches/{branch}/departments/{department}', [DepartmentController::class, 'destroy'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/organizations/{organization}/users', [UserController::class, 'index'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/organizations/{organization}/users/{user}', [UserController::class, 'show'])->middleware(EnsureOrganizationAccess::class);
        Route::put('/organizations/{organization}/users/{user}', [UserController::class, 'update'])->middleware(EnsureOrganizationAccess::class);

        Route::get('/patients', [PatientController::class, 'index'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/patients', [PatientController::class, 'store'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/patients/search', [PatientController::class, 'search'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/patients/duplicate-check', [PatientController::class, 'duplicateCheck'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/patients/duplicates', [PatientController::class, 'duplicates'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/patients/merge', [PatientController::class, 'merge'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->middleware(EnsureOrganizationAccess::class);
        Route::put('/patients/{patient}', [PatientController::class, 'update'])->middleware(EnsureOrganizationAccess::class);
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/patients/{patient}/identifiers', [PatientController::class, 'identifiers'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/patients/{patient}/identifiers', [PatientController::class, 'storeIdentifier'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/patients/{patient}/contacts', [PatientController::class, 'contacts'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/patients/{patient}/contacts', [PatientController::class, 'storeContact'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/patients/{patient}/addresses', [PatientController::class, 'addresses'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/patients/{patient}/addresses', [PatientController::class, 'storeAddress'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/patients/{patient}/documents', [PatientController::class, 'documents'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/patients/{patient}/documents', [PatientController::class, 'storeDocument'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/patients/{patient}/timeline', [PatientController::class, 'timeline'])->middleware(EnsureOrganizationAccess::class);

        Route::get('/appointments', [AppointmentController::class, 'index'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/appointments', [AppointmentController::class, 'store'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/appointments/search', [AppointmentController::class, 'search'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/appointments/availability', [AppointmentController::class, 'availability'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/appointments/calendar', [AppointmentController::class, 'calendar'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->middleware(EnsureOrganizationAccess::class);
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/appointments/{appointment}/no-show', [AppointmentController::class, 'noShow'])->middleware(EnsureOrganizationAccess::class);
        Route::post('/appointments/{appointment}/queue', [AppointmentController::class, 'queue'])->middleware(EnsureOrganizationAccess::class);

        Route::get('/providers', [ProviderController::class, 'index'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/providers/{provider}/schedule', [ProviderController::class, 'schedule'])->middleware(EnsureOrganizationAccess::class);
        Route::get('/providers/{provider}/availability', [ProviderController::class, 'availability'])->middleware(EnsureOrganizationAccess::class);

        Route::get('/encounters', [EncounterController::class, 'index'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters', [EncounterController::class, 'store'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::get('/encounters/{encounter}', [EncounterController::class, 'show'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::put('/encounters/{encounter}', [EncounterController::class, 'update'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::delete('/encounters/{encounter}', [EncounterController::class, 'destroy'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

        Route::post('/encounters/{encounter}/start', [EncounterController::class, 'start'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/pause', [EncounterController::class, 'pause'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/resume', [EncounterController::class, 'resume'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/complete', [EncounterController::class, 'complete'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/cancel', [EncounterController::class, 'cancel'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/transfer', [EncounterController::class, 'transfer'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/lock', [EncounterController::class, 'lock'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/encounters/{encounter}/summary', [EncounterController::class, 'summary'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/encounters/{encounter}/vitals', [EncounterController::class, 'vitals'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/vitals', [EncounterController::class, 'storeVital'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/encounters/{encounter}/diagnoses', [EncounterController::class, 'diagnoses'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/diagnoses', [EncounterController::class, 'storeDiagnosis'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/encounters/{encounter}/orders', [EncounterController::class, 'orders'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/orders', [EncounterController::class, 'storeOrder'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/encounters/{encounter}/prescriptions', [EncounterController::class, 'prescriptions'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/prescriptions', [EncounterController::class, 'storePrescription'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/prescriptions/{prescription}/issue', [EncounterController::class, 'issuePrescription'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/prescriptions/{prescription}/cancel', [EncounterController::class, 'cancelPrescription'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

        Route::post('/encounters/{encounter}/amendments', [EncounterController::class, 'storeAmendment'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
        Route::put('/encounters/{encounter}/amendments/{amendment}/approve', [EncounterController::class, 'approveAmendment'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
    });
});
