<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\EncounterController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\ProviderController;
use App\Http\Controllers\Api\V1\TenantContextController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Middleware\ApplyTenantContextToInput;
use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureCompanyAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum', ApplyTenantContextToInput::class])->group(function () {
        Route::get('/tenant/context', [TenantContextController::class, 'show']);
        Route::post('/tenant/context', [TenantContextController::class, 'update']);

        require __DIR__.'/../modules/Settings/Routes/api.php';
        require __DIR__.'/../modules/Notifications/Routes/api.php';
        require __DIR__.'/../modules/Radiology/Routes/api.php';
        require __DIR__.'/../modules/Pharmacy/Routes/api.php';
        require __DIR__.'/../modules/Ipd/Routes/api.php';
        require __DIR__.'/../modules/Nursing/Routes/api.php';

        Route::get('/companies', [CompanyController::class, 'index']);
        Route::post('/companies', [CompanyController::class, 'store']);
        Route::get('/companies/{company}', [CompanyController::class, 'show'])->middleware(EnsureCompanyAccess::class);
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->middleware(EnsureCompanyAccess::class);
        Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->middleware(EnsureCompanyAccess::class);

        Route::get('/companies/{company}/branches', [BranchController::class, 'index'])->middleware(EnsureCompanyAccess::class);
        Route::post('/companies/{company}/branches', [BranchController::class, 'store'])->middleware(EnsureCompanyAccess::class);
        Route::get('/companies/{company}/branches/{branch}', [BranchController::class, 'show'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::put('/companies/{company}/branches/{branch}', [BranchController::class, 'update'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::delete('/companies/{company}/branches/{branch}', [BranchController::class, 'destroy'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/companies/{company}/branches/{branch}/departments', [DepartmentController::class, 'index'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/companies/{company}/branches/{branch}/departments', [DepartmentController::class, 'store'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::get('/companies/{company}/branches/{branch}/departments/{department}', [DepartmentController::class, 'show'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::put('/companies/{company}/branches/{branch}/departments/{department}', [DepartmentController::class, 'update'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::delete('/companies/{company}/branches/{branch}/departments/{department}', [DepartmentController::class, 'destroy'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/companies/{company}/users', [UserController::class, 'index'])->middleware(EnsureCompanyAccess::class);
        Route::get('/companies/{company}/users/{user}', [UserController::class, 'show'])->middleware(EnsureCompanyAccess::class);
        Route::put('/companies/{company}/users/{user}', [UserController::class, 'update'])->middleware(EnsureCompanyAccess::class);

        Route::get('/patients', [PatientController::class, 'index'])->middleware(EnsureCompanyAccess::class);
        Route::post('/patients', [PatientController::class, 'store'])->middleware(EnsureCompanyAccess::class);
        Route::get('/patients/search', [PatientController::class, 'search'])->middleware(EnsureCompanyAccess::class);
        Route::post('/patients/duplicate-check', [PatientController::class, 'duplicateCheck'])->middleware(EnsureCompanyAccess::class);
        Route::get('/patients/duplicates', [PatientController::class, 'duplicates'])->middleware(EnsureCompanyAccess::class);
        Route::post('/patients/merge', [PatientController::class, 'merge'])->middleware(EnsureCompanyAccess::class);
        Route::get('/patients/{patient}', [PatientController::class, 'show'])->middleware(EnsureCompanyAccess::class);
        Route::put('/patients/{patient}', [PatientController::class, 'update'])->middleware(EnsureCompanyAccess::class);
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->middleware(EnsureCompanyAccess::class);
        Route::get('/patients/{patient}/identifiers', [PatientController::class, 'identifiers'])->middleware(EnsureCompanyAccess::class);
        Route::post('/patients/{patient}/identifiers', [PatientController::class, 'storeIdentifier'])->middleware(EnsureCompanyAccess::class);
        Route::get('/patients/{patient}/contacts', [PatientController::class, 'contacts'])->middleware(EnsureCompanyAccess::class);
        Route::post('/patients/{patient}/contacts', [PatientController::class, 'storeContact'])->middleware(EnsureCompanyAccess::class);
        Route::get('/patients/{patient}/addresses', [PatientController::class, 'addresses'])->middleware(EnsureCompanyAccess::class);
        Route::post('/patients/{patient}/addresses', [PatientController::class, 'storeAddress'])->middleware(EnsureCompanyAccess::class);
        Route::get('/patients/{patient}/documents', [PatientController::class, 'documents'])->middleware(EnsureCompanyAccess::class);
        Route::post('/patients/{patient}/documents', [PatientController::class, 'storeDocument'])->middleware(EnsureCompanyAccess::class);
        Route::get('/patients/{patient}/timeline', [PatientController::class, 'timeline'])->middleware(EnsureCompanyAccess::class);

        Route::get('/appointments', [AppointmentController::class, 'index'])->middleware(EnsureCompanyAccess::class);
        Route::post('/appointments', [AppointmentController::class, 'store'])->middleware(EnsureCompanyAccess::class);
        Route::get('/appointments/search', [AppointmentController::class, 'search'])->middleware(EnsureCompanyAccess::class);
        Route::get('/appointments/availability', [AppointmentController::class, 'availability'])->middleware(EnsureCompanyAccess::class);
        Route::get('/appointments/calendar', [AppointmentController::class, 'calendar'])->middleware(EnsureCompanyAccess::class);
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->middleware(EnsureCompanyAccess::class);
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->middleware(EnsureCompanyAccess::class);
        Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->middleware(EnsureCompanyAccess::class);
        Route::post('/appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->middleware(EnsureCompanyAccess::class);
        Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->middleware(EnsureCompanyAccess::class);
        Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->middleware(EnsureCompanyAccess::class);
        Route::post('/appointments/{appointment}/no-show', [AppointmentController::class, 'noShow'])->middleware(EnsureCompanyAccess::class);
        Route::post('/appointments/{appointment}/queue', [AppointmentController::class, 'queue'])->middleware(EnsureCompanyAccess::class);

        Route::get('/providers', [ProviderController::class, 'index'])->middleware(EnsureCompanyAccess::class);
        Route::get('/providers/{provider}/schedule', [ProviderController::class, 'schedule'])->middleware(EnsureCompanyAccess::class);
        Route::get('/providers/{provider}/availability', [ProviderController::class, 'availability'])->middleware(EnsureCompanyAccess::class);

        Route::get('/encounters', [EncounterController::class, 'index'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters', [EncounterController::class, 'store'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::get('/encounters/{encounter}', [EncounterController::class, 'show'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::put('/encounters/{encounter}', [EncounterController::class, 'update'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::delete('/encounters/{encounter}', [EncounterController::class, 'destroy'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

        Route::post('/encounters/{encounter}/start', [EncounterController::class, 'start'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/pause', [EncounterController::class, 'pause'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/resume', [EncounterController::class, 'resume'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/complete', [EncounterController::class, 'complete'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/cancel', [EncounterController::class, 'cancel'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/transfer', [EncounterController::class, 'transfer'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/lock', [EncounterController::class, 'lock'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/encounters/{encounter}/summary', [EncounterController::class, 'summary'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/encounters/{encounter}/vitals', [EncounterController::class, 'vitals'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/vitals', [EncounterController::class, 'storeVital'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/encounters/{encounter}/diagnoses', [EncounterController::class, 'diagnoses'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/diagnoses', [EncounterController::class, 'storeDiagnosis'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/encounters/{encounter}/orders', [EncounterController::class, 'orders'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/orders', [EncounterController::class, 'storeOrder'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/encounters/{encounter}/prescriptions', [EncounterController::class, 'prescriptions'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/prescriptions', [EncounterController::class, 'storePrescription'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/prescriptions/{prescription}/issue', [EncounterController::class, 'issuePrescription'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/encounters/{encounter}/prescriptions/{prescription}/cancel', [EncounterController::class, 'cancelPrescription'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

        Route::post('/encounters/{encounter}/amendments', [EncounterController::class, 'storeAmendment'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::put('/encounters/{encounter}/amendments/{amendment}/approve', [EncounterController::class, 'approveAmendment'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
    });
});
