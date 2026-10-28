<?php

use Illuminate\Support\Facades\Route;
use Modules\Portal\Http\Controllers\Api\V1\PortalAccountApiController;
use Modules\Portal\Http\Controllers\Api\V1\PortalAuthApiController;
use Modules\Portal\Http\Controllers\Api\V1\PortalCareApiController;
use Modules\Portal\Http\Controllers\Api\V1\PortalMoneyApiController;
use Modules\Portal\Http\Controllers\Api\V1\PortalTalkApiController;

// The patient mobile API: /api/v1/portal/*. A different login from staff — Sanctum tokens whose owner is a patient account and whose only ability is `portal`.
Route::prefix('portal')->name('api.portal.')->group(function () {
    Route::get('config', [PortalAuthApiController::class, 'config'])->name('config');

    Route::middleware('throttle:20,1')->group(function () {
        Route::post('auth/register/start', [PortalAuthApiController::class, 'registerStart'])->name('register.start');
        Route::post('auth/register/complete', [PortalAuthApiController::class, 'registerComplete'])->name('register.complete');
        Route::post('auth/invitation', [PortalAuthApiController::class, 'claimInvitation'])->name('invitation');
        Route::post('auth/login', [PortalAuthApiController::class, 'login'])->name('login');
        Route::post('auth/login/otp', [PortalAuthApiController::class, 'loginOtp'])->name('login.otp');
        Route::post('auth/password/forgot', [PortalAuthApiController::class, 'forgot'])->name('password.forgot');
        Route::post('auth/password/reset', [PortalAuthApiController::class, 'reset'])->name('password.reset');
        Route::post('payments/callback', [PortalMoneyApiController::class, 'callback'])->name('payments.callback');
    });

    Route::middleware(['auth:sanctum', 'portal.auth:api', 'module:portal', 'portal.etag', 'throttle:120,1'])->group(function () {
        Route::post('auth/logout', [PortalAuthApiController::class, 'logout'])->name('logout');
        Route::get('auth/devices', [PortalAuthApiController::class, 'devices'])->name('devices');
        Route::delete('auth/devices/{device}', [PortalAuthApiController::class, 'revokeDevice'])->whereNumber('device')->name('devices.revoke');
        Route::post('auth/password/change', [PortalAuthApiController::class, 'changePassword'])->name('password.change');
        Route::post('auth/push-token', [PortalAuthApiController::class, 'pushToken'])->name('push-token');

        Route::get('me', [PortalAccountApiController::class, 'me'])->name('me');
        Route::get('home', [PortalAccountApiController::class, 'home'])->name('home');
        Route::get('me/export', [PortalAccountApiController::class, 'export'])->middleware('throttle:6,60')->name('export');
        Route::post('me/close', [PortalAccountApiController::class, 'close'])->middleware('throttle:5,60')->name('close');
        Route::get('sync', [PortalAccountApiController::class, 'sync'])->name('sync');
        Route::get('appointments/{appointment}/video', [PortalAccountApiController::class, 'video'])->name('video');
        Route::get('notifications', [PortalAccountApiController::class, 'notifications'])->name('notifications');
        Route::post('notifications/read', [PortalAccountApiController::class, 'readNotifications'])->name('notifications.read');
        Route::get('preferences', [PortalAccountApiController::class, 'preferences'])->name('preferences');
        Route::put('preferences', [PortalAccountApiController::class, 'setPreference'])->name('preferences.set');
        Route::get('consents', [PortalAccountApiController::class, 'consents'])->name('consents');
        Route::put('consents', [PortalAccountApiController::class, 'setConsent'])->name('consents.set');
        Route::get('family', [PortalAccountApiController::class, 'family'])->name('family');
        Route::post('family/requests', [PortalAccountApiController::class, 'requestProxy'])->name('family.request');
        Route::delete('family/{proxy}', [PortalAccountApiController::class, 'endProxy'])->name('family.end');

        Route::get('providers', [PortalCareApiController::class, 'providers'])->name('providers');
        Route::get('providers/{provider}/slots', [PortalCareApiController::class, 'slots'])->name('slots');
        Route::get('appointments', [PortalCareApiController::class, 'appointments'])->name('appointments');
        Route::post('appointments', [PortalCareApiController::class, 'book'])->name('appointments.book');
        Route::post('appointments/{appointment}/cancel', [PortalCareApiController::class, 'cancel'])->name('appointments.cancel');
        Route::post('appointments/{appointment}/reschedule', [PortalCareApiController::class, 'reschedule'])->name('appointments.reschedule');
        Route::get('appointments/{appointment}/queue', [PortalCareApiController::class, 'queue'])->name('appointments.queue');
        Route::get('records/summary', [PortalCareApiController::class, 'summary'])->name('records');
        Route::get('results', [PortalCareApiController::class, 'results'])->name('results');
        Route::get('results/{result}', [PortalCareApiController::class, 'result'])->name('results.show');
        Route::get('prescriptions', [PortalCareApiController::class, 'prescriptions'])->name('prescriptions');
        Route::post('prescriptions/{prescription}/refill', [PortalCareApiController::class, 'refill'])->name('prescriptions.refill');
        Route::get('refill-requests', [PortalCareApiController::class, 'refillRequests'])->name('refills');
        Route::post('documents/share', [PortalCareApiController::class, 'share'])->name('documents.share');
        Route::get('documents/{token}', [PortalCareApiController::class, 'document'])->name('documents.open');

        Route::get('invoices', [PortalMoneyApiController::class, 'invoices'])->name('invoices');
        Route::get('invoices/{invoice}', [PortalMoneyApiController::class, 'invoice'])->name('invoices.show');
        Route::post('invoices/{invoice}/pay', [PortalMoneyApiController::class, 'pay'])->name('invoices.pay');
        Route::get('payments/{payment}', [PortalMoneyApiController::class, 'payment'])->name('payments.show');
        Route::get('receipts', [PortalMoneyApiController::class, 'receipts'])->name('receipts');

        Route::get('threads', [PortalTalkApiController::class, 'threads'])->name('threads');
        Route::post('threads', [PortalTalkApiController::class, 'start'])->name('threads.start');
        Route::get('threads/{thread}', [PortalTalkApiController::class, 'thread'])->name('threads.show');
        Route::post('threads/{thread}/reply', [PortalTalkApiController::class, 'reply'])->name('threads.reply');
        Route::get('service-requests', [PortalTalkApiController::class, 'serviceRequests'])->name('requests');
        Route::post('service-requests', [PortalTalkApiController::class, 'storeServiceRequest'])->name('requests.store');
        Route::post('appointments/{appointment}/feedback', [PortalTalkApiController::class, 'rate'])->name('feedback');
        Route::get('complaints', [PortalTalkApiController::class, 'myComplaints'])->name('complaints');
        Route::post('complaints', [PortalTalkApiController::class, 'complaint'])->name('complaints.store');
        Route::post('complaints/{number}/rating', [PortalTalkApiController::class, 'rateComplaint'])->name('complaints.rate');
        Route::get('forms', [PortalTalkApiController::class, 'forms'])->name('forms');
        Route::post('forms/{code}', [PortalTalkApiController::class, 'submitForm'])->name('forms.submit');
    });
});
