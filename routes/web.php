<?php

use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DoctorScheduleController;
use App\Http\Controllers\Admin\EncounterController;
use App\Http\Controllers\Admin\MasterData\MasterDataController;
use App\Http\Controllers\Admin\OpdConsultationController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\Mfa\MfaController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureCompanyAccess;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\RequireMfa;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('home');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/login/context', [LoginController::class, 'showCompanyBranchForm'])->name('login.context');
Route::post('/login/context', [LoginController::class, 'storeCompanyBranch'])->name('login.context.store');

Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');

Route::middleware(['auth'])->group(function () {
    Route::get('/change-password', [PasswordController::class, 'create'])->name('password.change');
    Route::post('/change-password', [PasswordController::class, 'store']);

    Route::middleware([RequireMfa::class])->group(function () {
        Route::middleware([EnsureTenantContext::class])->group(function () {
            Route::get('/dashboard', [HomeController::class, 'index'])->name('home');
        });
    });

    Route::get('/mfa/challenge', [MfaController::class, 'showChallenge'])->name('mfa.challenge');
    Route::post('/mfa/verify', [MfaController::class, 'verify'])->name('mfa.verify');
    Route::get('/mfa/setup', [MfaController::class, 'showSetup'])->name('mfa.setup');
    Route::post('/mfa/setup', [MfaController::class, 'setup'])->name('mfa.setup.store');
    Route::post('/mfa/disable', [MfaController::class, 'disable'])->name('mfa.disable');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('can:manage companies')->group(function () {
            Route::resource('companies', CompanyController::class);

            Route::prefix('companies/{company}')->name('companies.')->middleware(EnsureCompanyAccess::class)->group(function () {
                Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
                Route::get('branches/create', [BranchController::class, 'create'])->name('branches.create');
                Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
                Route::get('branches/{branch}', [BranchController::class, 'show'])->name('branches.show');
                Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
                Route::put('branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
                Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');

                Route::prefix('branches/{branch}')->name('branches.')->middleware(EnsureBranchAccess::class)->group(function () {
                    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
                    Route::get('departments/create', [DepartmentController::class, 'create'])->name('departments.create');
                    Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
                    Route::get('departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
                    Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
                    Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
                    Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
                });
            });

            Route::resource('users', UserController::class)->except(['show']);

            Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

            Route::prefix('companies/{company}')->name('companies.')->middleware(EnsureCompanyAccess::class)->group(function () {
                Route::get('users', [UserController::class, 'companyIndex'])->name('users.company-index');
                Route::get('users/{user}', [UserController::class, 'show'])->name('users.company-show');
                Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.company-edit');
                Route::put('users/{user}', [UserController::class, 'update'])->name('users.company-update');
            });

            Route::get('departments', [DepartmentController::class, 'globalIndex'])->name('departments.global');
        Route::get('departments/create', [DepartmentController::class, 'createGlobal'])->name('departments.create.global');

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

            Route::resource('patients', PatientController::class);

            Route::resource('encounters', EncounterController::class);

            // Phase 2: Appointment + OPD
            Route::resource('appointments', AppointmentController::class);
            Route::resource('schedules', DoctorScheduleController::class)->except(['show']);
            Route::get('schedules/{schedule}', [DoctorScheduleController::class, 'show'])->name('schedules.show');

            // Queue management
            Route::get('queue', [QueueController::class, 'index'])->name('queue.index');
            Route::post('queue/call-next', [QueueController::class, 'callNext'])->name('queue.call-next');
            Route::post('queue/{token}/start', [QueueController::class, 'start'])->name('queue.start');
            Route::post('queue/{token}/complete', [QueueController::class, 'complete'])->name('queue.complete');
            Route::get('queue/slots', [QueueController::class, 'searchSlots'])->name('queue.slots');

            Route::post('appointments/{appointment}/check-in', [OpdConsultationController::class, 'checkIn'])->name('appointments.check-in');
            Route::post('appointments/{appointment}/follow-up', [OpdConsultationController::class, 'createFollowUp'])->name('appointments.follow-up');

            // OPD consultation
            Route::get('appointments/{appointment}/consultation', [OpdConsultationController::class, 'show'])->name('opd.consultation');
            Route::post('opd/vital-signs', [OpdConsultationController::class, 'storeVitalSigns'])->name('opd.vital-signs');
            Route::post('opd/diagnoses', [OpdConsultationController::class, 'storeDiagnosis'])->name('opd.diagnoses');
            Route::post('opd/investigation-orders', [OpdConsultationController::class, 'storeInvestigationOrder'])->name('opd.investigation-orders');
            Route::post('opd/prescriptions', [OpdConsultationController::class, 'storePrescription'])->name('opd.prescriptions');
            Route::post('appointments/{appointment}/status/in-progress', [OpdConsultationController::class, 'markInProgress'])->name('opd.status.in-progress');
            Route::post('appointments/{appointment}/status/completed', [OpdConsultationController::class, 'markCompleted'])->name('opd.status.completed');

            Route::resource('roles', RoleController::class)->except(['show']);
            Route::resource('permissions', PermissionController::class)->except(['show']);
        });

        Route::middleware(['can:audit.view'])->group(function () {
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
            Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit.show');
        });

        Route::get('notifications', function () {
            return view('admin.notifications.index');
        })->name('notifications.index');

        Route::get('messages', function () {
            return view('admin.messages.index');
        })->name('messages.index');

        Route::middleware(['can:settings.view'])->group(function () {
            Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');

            Route::get('master-data/countries', [MasterDataController::class, 'countries'])->name('master-data.countries');
            Route::get('master-data/countries/create', [MasterDataController::class, 'createCountry'])->name('master-data.countries.create');
            Route::post('master-data/countries', [MasterDataController::class, 'storeCountry'])->name('master-data.countries.store');
            Route::get('master-data/countries/{country}/edit', [MasterDataController::class, 'editCountry'])->name('master-data.countries.edit');
            Route::put('master-data/countries/{country}', [MasterDataController::class, 'updateCountry'])->name('master-data.countries.update');
            Route::delete('master-data/countries/{country}', [MasterDataController::class, 'destroyCountry'])->name('master-data.countries.destroy');

            Route::get('master-data/states', [MasterDataController::class, 'allStates'])->name('master-data.all-states');
            Route::get('master-data/states/create', [MasterDataController::class, 'createState'])->name('master-data.states.create');
            Route::post('master-data/states', [MasterDataController::class, 'storeState'])->name('master-data.states.store');
            Route::get('master-data/states/{state}/edit', [MasterDataController::class, 'editState'])->name('master-data.states.edit');
            Route::put('master-data/states/{state}', [MasterDataController::class, 'updateState'])->name('master-data.states.update');
            Route::delete('master-data/states/{state}', [MasterDataController::class, 'destroyState'])->name('master-data.states.destroy');
            Route::get('master-data/countries/{country}/states', [MasterDataController::class, 'states'])->name('master-data.states.by-country');

            Route::get('master-data/currencies', [MasterDataController::class, 'currencies'])->name('master-data.currencies');
            Route::get('master-data/currencies/create', [MasterDataController::class, 'createCurrency'])->name('master-data.currencies.create');
            Route::post('master-data/currencies', [MasterDataController::class, 'storeCurrency'])->name('master-data.currencies.store');
            Route::get('master-data/currencies/{currency}/edit', [MasterDataController::class, 'editCurrency'])->name('master-data.currencies.edit');
            Route::put('master-data/currencies/{currency}', [MasterDataController::class, 'updateCurrency'])->name('master-data.currencies.update');
            Route::delete('master-data/currencies/{currency}', [MasterDataController::class, 'destroyCurrency'])->name('master-data.currencies.destroy');

            Route::get('master-data/identification-types', [MasterDataController::class, 'identificationTypes'])->name('master-data.identification-types');
            Route::get('master-data/identification-types/create', [MasterDataController::class, 'createIdentificationType'])->name('master-data.identification-types.create');
            Route::post('master-data/identification-types', [MasterDataController::class, 'storeIdentificationType'])->name('master-data.identification-types.store');
            Route::get('master-data/identification-types/{identificationType}/edit', [MasterDataController::class, 'editIdentificationType'])->name('master-data.identification-types.edit');
            Route::put('master-data/identification-types/{identificationType}', [MasterDataController::class, 'updateIdentificationType'])->name('master-data.identification-types.update');
            Route::delete('master-data/identification-types/{identificationType}', [MasterDataController::class, 'destroyIdentificationType'])->name('master-data.identification-types.destroy');
        });
    });
});
