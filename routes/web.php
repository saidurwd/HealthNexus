<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\Mfa\MfaController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DocumentPrintController;
use App\Http\Controllers\DowntimeController;
use App\Http\Controllers\GlobalSearchPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\QuickStartController;
use App\Http\Middleware\ApplyTenantContextToInput;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\RequireMfa;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('home');
});

Route::post('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/login/context', [LoginController::class, 'showOrganizationBranchForm'])->name('login.context');
Route::post('/login/context', [LoginController::class, 'storeOrganizationBranch'])->name('login.context.store');

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

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/mfa/challenge', [MfaController::class, 'showChallenge'])->name('mfa.challenge');
    Route::post('/mfa/verify', [MfaController::class, 'verify'])->name('mfa.verify');
    Route::get('/mfa/setup', [MfaController::class, 'showSetup'])->name('mfa.setup');
    Route::post('/mfa/setup', [MfaController::class, 'setup'])->name('mfa.setup.store');
    Route::post('/mfa/disable', [MfaController::class, 'disable'])->name('mfa.disable');

    Route::prefix('admin')->name('admin.')->middleware([ApplyTenantContextToInput::class])->group(function () {
        require __DIR__.'/../modules/Core/Organization/Routes/web.php';
        require __DIR__.'/../modules/Rbac/Routes/web.php';
        require __DIR__.'/../modules/Patients/Routes/web.php';
        require __DIR__.'/../modules/Clinical/Routes/web.php';
        require __DIR__.'/../modules/Appointments/Routes/web.php';
        Route::middleware('module:billing')->group(fn () => require __DIR__.'/../modules/Billing/Routes/web.php');
        Route::middleware('module:lab')->group(fn () => require __DIR__.'/../modules/Laboratory/Routes/web.php');
        Route::middleware('module:radiology')->group(fn () => require __DIR__.'/../modules/Radiology/Routes/web.php');
        Route::middleware('module:pharmacy')->group(fn () => require __DIR__.'/../modules/Pharmacy/Routes/web.php');
        Route::middleware('module:ipd')->group(fn () => require __DIR__.'/../modules/Ipd/Routes/web.php');
        Route::middleware('module:nursing')->group(fn () => require __DIR__.'/../modules/Nursing/Routes/web.php');
        Route::middleware('module:ot')->group(fn () => require __DIR__.'/../modules/Ot/Routes/web.php');
        Route::middleware('module:icu')->group(fn () => require __DIR__.'/../modules/Icu/Routes/web.php');
        require __DIR__.'/../modules/Audit/Routes/web.php';
        require __DIR__.'/../modules/System/Routes/web.php';
        require __DIR__.'/../modules/Workflow/Routes/web.php';
        require __DIR__.'/../modules/Settings/Routes/web.php';
        require __DIR__.'/../modules/Files/Routes/web.php';

        Route::get('notifications', function () {
            return view('admin.notifications.index');
        })->name('notifications.index');

        Route::prefix('imports')->name('imports.')->group(function () {
            Route::get('/', [ImportController::class, 'index'])->name('index');
            Route::post('/', [ImportController::class, 'store'])->name('store');
            Route::get('template/{key}', [ImportController::class, 'template'])->name('template');
            Route::get('{batch}', [ImportController::class, 'show'])->name('show');
            Route::post('{batch}/commit', [ImportController::class, 'commit'])->name('commit');
            Route::post('{batch}/rollback', [ImportController::class, 'rollback'])->name('rollback');
            Route::get('{batch}/errors', [ImportController::class, 'errors'])->name('errors');
        });

        Route::prefix('downtime')->name('downtime.')->group(function () {
            Route::get('/', [DowntimeController::class, 'index'])->name('index');
            Route::get('census', [DowntimeController::class, 'census'])->name('census');
            Route::get('forms/{form}', [DowntimeController::class, 'form'])->name('form');
        });

        Route::get('help/quick-start', [QuickStartController::class, 'index'])->name('help.quick-start');
        Route::get('help/quick-start/{role}', [QuickStartController::class, 'show'])->name('help.quick-start.show');

        Route::get('search', GlobalSearchPageController::class)->name('search');
        Route::get('documents/{type}/{reference}', DocumentPrintController::class)->name('documents.print');

        Route::get('messages', function () {
            return view('admin.messages.index');
        })->name('messages.index');

        require __DIR__.'/../modules/MasterData/Routes/web.php';
    });
});
