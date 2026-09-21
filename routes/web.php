<?php

use App\Http\Controllers\Admin\Audit\AuditLogController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EncounterController;
use App\Http\Controllers\Admin\MasterData\MasterDataController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\PermissionController;
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

    Route::prefix('admin')->name('admin.')->middleware('can:manage companies')->group(function () {
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

        Route::prefix('companies/{company}')->name('companies.')->middleware(EnsureCompanyAccess::class)->group(function () {
            Route::get('users', [UserController::class, 'companyIndex'])->name('users.index');
            Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        });

        Route::get('departments', [DepartmentController::class, 'globalIndex'])->name('departments.global');

        Route::resource('patients', PatientController::class);

        Route::resource('encounters', EncounterController::class);

        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('permissions', PermissionController::class)->except(['show']);

        Route::middleware(['can:view audit logs'])->group(function () {
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
            Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit.show');
        });

        Route::middleware(['can:manage settings'])->group(function () {
            Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');
            Route::get('master-data/countries', [MasterDataController::class, 'countries'])->name('master-data.countries');
            Route::get('master-data/states', [MasterDataController::class, 'allStates'])->name('master-data.all-states');
            Route::get('master-data/countries/{country}/states', [MasterDataController::class, 'states'])->name('master-data.states');
            Route::get('master-data/currencies', [MasterDataController::class, 'currencies'])->name('master-data.currencies');
            Route::get('master-data/identification-types', [MasterDataController::class, 'identificationTypes'])->name('master-data.identification-types');
        });
    });
});
