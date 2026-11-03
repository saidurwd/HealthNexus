<?php

use Illuminate\Support\Facades\Route;
use Modules\Portal\Http\Controllers\Web\PortalWebAuthController;
use Modules\Portal\Http\Controllers\Web\PortalWebCareController;
use Modules\Portal\Http\Controllers\Web\PortalWebHomeController;

// The patient's own light web pages: /portal/*. A different login from staff (guard `portal`).
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', fn () => redirect()->route('portal.home'));
    Route::get('language/{lang}', [PortalWebAuthController::class, 'language'])->name('language');

    Route::middleware(['throttle:30,1'])->group(function () {
        Route::get('login', [PortalWebAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [PortalWebAuthController::class, 'login'])->name('login.attempt');
        Route::get('login/code', [PortalWebAuthController::class, 'showCode'])->name('login.code');
        Route::post('login/code', [PortalWebAuthController::class, 'verifyCode'])->name('login.code.verify');
        Route::get('register', [PortalWebAuthController::class, 'showRegister'])->name('register');
        Route::post('register', [PortalWebAuthController::class, 'register'])->name('register.start');
        Route::get('register/verify', [PortalWebAuthController::class, 'showVerify'])->name('register.verify');
        Route::post('register/verify', [PortalWebAuthController::class, 'verify'])->name('register.complete');
        Route::get('activate', [PortalWebAuthController::class, 'showActivate'])->name('activate');
        Route::post('activate', [PortalWebAuthController::class, 'activate'])->name('activate.claim');
        Route::get('forgot', [PortalWebAuthController::class, 'showForgot'])->name('forgot');
        Route::post('forgot', [PortalWebAuthController::class, 'forgot'])->name('forgot.start');
        Route::get('reset', [PortalWebAuthController::class, 'showReset'])->name('reset');
        Route::post('reset', [PortalWebAuthController::class, 'reset'])->name('reset.complete');
    });

    Route::middleware(['portal.auth', 'module:portal', 'throttle:120,1'])->group(function () {
        Route::post('logout', [PortalWebAuthController::class, 'logout'])->name('logout');
        Route::get('home', [PortalWebHomeController::class, 'home'])->name('home');
        Route::post('switch', [PortalWebHomeController::class, 'switch'])->name('switch');
        Route::get('notifications', [PortalWebHomeController::class, 'notifications'])->name('notifications');
        Route::get('help', [PortalWebHomeController::class, 'help'])->name('help');

        Route::get('records', [PortalWebHomeController::class, 'records'])->name('records');
        Route::get('results', [PortalWebHomeController::class, 'results'])->name('results');
        Route::get('results/{result}', [PortalWebHomeController::class, 'result'])->name('results.show');
        Route::get('prescriptions', [PortalWebHomeController::class, 'prescriptions'])->name('prescriptions');
        Route::post('prescriptions/{prescription}/refill', [PortalWebHomeController::class, 'refill'])->name('prescriptions.refill');
        Route::post('documents/share', [PortalWebHomeController::class, 'share'])->name('documents.share');
        Route::get('documents/{token}', [PortalWebHomeController::class, 'document'])->name('documents.open');

        Route::get('family', [PortalWebHomeController::class, 'family'])->name('family');
        Route::post('family/requests', [PortalWebHomeController::class, 'requestProxy'])->name('family.request');
        Route::post('family/{proxy}/end', [PortalWebHomeController::class, 'endProxy'])->name('family.end');
        Route::get('settings', [PortalWebHomeController::class, 'settings'])->name('settings');
        Route::post('settings/preferences', [PortalWebHomeController::class, 'savePreferences'])->name('settings.preferences');
        Route::get('settings/export', [PortalWebHomeController::class, 'export'])->middleware('throttle:6,60')->name('settings.export');
        Route::post('settings/close', [PortalWebHomeController::class, 'close'])->middleware('throttle:5,60')->name('settings.close');
        Route::post('settings/password', [PortalWebHomeController::class, 'changePassword'])->name('settings.password');
        Route::post('settings/devices/{device}/sign-out', [PortalWebHomeController::class, 'signOutDevice'])->whereNumber('device')->name('settings.devices.out');

        Route::get('appointments', [PortalWebCareController::class, 'appointments'])->name('appointments');
        Route::get('appointments/book', [PortalWebCareController::class, 'book'])->name('appointments.book');
        Route::post('appointments/book', [PortalWebCareController::class, 'confirmBooking'])->name('appointments.confirm');
        Route::post('appointments/{appointment}/cancel', [PortalWebCareController::class, 'cancelAppointment'])->name('appointments.cancel');
        Route::post('appointments/{appointment}/rate', [PortalWebCareController::class, 'rate'])->name('appointments.rate');

        Route::get('billing', [PortalWebCareController::class, 'billing'])->name('billing');
        Route::post('billing/{invoice}/pay', [PortalWebCareController::class, 'pay'])->name('billing.pay');
        Route::get('pay/return/{payment}', [PortalWebCareController::class, 'payReturn'])->name('pay.return');

        Route::get('messages', [PortalWebCareController::class, 'threads'])->name('threads');
        Route::post('messages', [PortalWebCareController::class, 'startThread'])->name('threads.start');
        Route::get('messages/{thread}', [PortalWebCareController::class, 'thread'])->name('threads.show');
        Route::post('messages/{thread}', [PortalWebCareController::class, 'replyThread'])->name('threads.reply');
        Route::get('requests', [PortalWebCareController::class, 'serviceRequests'])->name('requests');
        Route::post('requests', [PortalWebCareController::class, 'storeServiceRequest'])->name('requests.store');
        Route::get('complaints', [PortalWebCareController::class, 'complaints'])->name('complaints');
        Route::post('complaints', [PortalWebCareController::class, 'storeComplaint'])->name('complaints.store');
        Route::post('complaints/{number}/rating', [PortalWebCareController::class, 'rateComplaint'])->name('complaints.rate');
        Route::get('forms', [PortalWebCareController::class, 'forms'])->name('forms');
        Route::get('forms/{code}', [PortalWebCareController::class, 'form'])->name('forms.show');
        Route::post('forms/{code}', [PortalWebCareController::class, 'submitForm'])->name('forms.submit');
    });
});
