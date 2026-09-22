<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\Mfa\MfaController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
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
        require __DIR__.'/../modules/Core/Organization/Routes/web.php';
        require __DIR__.'/../modules/Rbac/Routes/web.php';
        require __DIR__.'/../modules/Patients/Routes/web.php';
        require __DIR__.'/../modules/Clinical/Routes/web.php';
        require __DIR__.'/../modules/Appointments/Routes/web.php';
        require __DIR__.'/../modules/Billing/Routes/web.php';
        require __DIR__.'/../modules/Audit/Routes/web.php';
        require __DIR__.'/../modules/Settings/Routes/web.php';
        require __DIR__.'/../modules/Files/Routes/web.php';

        Route::get('notifications', function () {
            return view('admin.notifications.index');
        })->name('notifications.index');

        Route::get('messages', function () {
            return view('admin.messages.index');
        })->name('messages.index');

        require __DIR__.'/../modules/MasterData/Routes/web.php';
    });
});
