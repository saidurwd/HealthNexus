<?php

use Illuminate\Support\Facades\Route;
use Modules\Portal\Http\Controllers\Web\CrmCampaignController;
use Modules\Portal\Http\Controllers\Web\CrmComplaintController;
use Modules\Portal\Http\Controllers\Web\CrmDeskController;
use Modules\Portal\Http\Controllers\Web\PortalDeskController;
use Modules\Portal\Http\Controllers\Web\PortalInboxController;
use Modules\Portal\Http\Controllers\Web\PortalPaymentDeskController;
use Modules\Portal\Http\Controllers\Web\PortalReleaseDeskController;

// Staff side of the patient portal and CRM: /admin/portal/* and /admin/crm/*.
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalDeskController::class, 'dashboard'])->name('dashboard');
    Route::get('accounts', [PortalDeskController::class, 'accounts'])->name('accounts');
    Route::post('accounts/invite', [PortalDeskController::class, 'invite'])->name('accounts.invite');
    Route::get('accounts/{account}', [PortalDeskController::class, 'show'])->name('accounts.show');
    Route::post('accounts/{account}/status', [PortalDeskController::class, 'status'])->name('accounts.status');
    Route::post('accounts/{account}/sign-out', [PortalDeskController::class, 'signOutEverywhere'])->name('accounts.sign-out');
    Route::get('proxies', [PortalDeskController::class, 'proxies'])->name('proxies');
    Route::post('proxies/{proxy}', [PortalDeskController::class, 'decideProxy'])->name('proxies.decide');
    Route::get('outbox', [PortalDeskController::class, 'outbox'])->name('outbox');
    Route::get('access-log', [PortalDeskController::class, 'accessLog'])->name('access-log');
    Route::post('links/{document}/revoke', [PortalDeskController::class, 'revokeLink'])->name('links.revoke');
    Route::get('settings', [PortalDeskController::class, 'settings'])->name('settings');
    Route::post('settings', [PortalDeskController::class, 'saveSettings'])->name('settings.save');

    Route::get('releases', [PortalReleaseDeskController::class, 'queue'])->name('releases');
    Route::post('releases/{release}', [PortalReleaseDeskController::class, 'decide'])->name('releases.decide');
    Route::get('release-rules', [PortalReleaseDeskController::class, 'policies'])->name('policies');
    Route::post('release-rules', [PortalReleaseDeskController::class, 'savePolicy'])->name('policies.save');
    Route::get('refills', [PortalReleaseDeskController::class, 'refills'])->name('refills');
    Route::post('refills/{refill}', [PortalReleaseDeskController::class, 'decideRefill'])->name('refills.decide');

    Route::get('messages', [PortalInboxController::class, 'threads'])->name('threads');
    Route::get('messages/{thread}', [PortalInboxController::class, 'thread'])->name('threads.show');
    Route::post('messages/{thread}/reply', [PortalInboxController::class, 'reply'])->name('threads.reply');
    Route::post('messages/{thread}/assign', [PortalInboxController::class, 'assign'])->name('threads.assign');
    Route::post('messages/{thread}/close', [PortalInboxController::class, 'close'])->name('threads.close');
    Route::get('requests', [PortalInboxController::class, 'requests'])->name('requests');
    Route::post('requests/{serviceRequest}', [PortalInboxController::class, 'updateRequest'])->name('requests.update');

    Route::get('payments', [PortalPaymentDeskController::class, 'index'])->name('payments');
    Route::post('payments/sweep', [PortalPaymentDeskController::class, 'sweep'])->name('payments.sweep');
    Route::post('settlements', [PortalPaymentDeskController::class, 'reconcile'])->name('settlements.import');
    Route::get('settlements/{settlement}', [PortalPaymentDeskController::class, 'settlement'])->name('settlements.show');
});

Route::prefix('crm')->name('crm.')->group(function () {
    Route::get('/', [CrmDeskController::class, 'dashboard'])->name('dashboard');
    Route::get('interactions', [CrmDeskController::class, 'interactions'])->name('interactions');
    Route::post('interactions', [CrmDeskController::class, 'storeInteraction'])->name('interactions.store');
    Route::post('interactions/{interaction}', [CrmDeskController::class, 'updateInteraction'])->name('interactions.update');
    Route::get('feedback', [CrmDeskController::class, 'feedback'])->name('feedback');
    Route::post('feedback/{feedback}/review', [CrmDeskController::class, 'reviewFeedback'])->name('feedback.review');

    Route::get('segments', [CrmCampaignController::class, 'segments'])->name('segments');
    Route::post('segments', [CrmCampaignController::class, 'storeSegment'])->name('segments.store');
    Route::post('segments/{segment}', [CrmCampaignController::class, 'segmentAction'])->name('segments.action');
    Route::get('campaigns', [CrmCampaignController::class, 'campaigns'])->name('campaigns');
    Route::post('campaigns', [CrmCampaignController::class, 'storeCampaign'])->name('campaigns.store');
    Route::get('campaigns/{campaign}', [CrmCampaignController::class, 'show'])->name('campaigns.show');
    Route::post('campaigns/{campaign}', [CrmCampaignController::class, 'action'])->name('campaigns.action');

    Route::get('complaints', [CrmComplaintController::class, 'index'])->name('complaints');
    Route::post('complaints', [CrmComplaintController::class, 'store'])->name('complaints.store');
    Route::get('complaints/{complaint}', [CrmComplaintController::class, 'show'])->name('complaints.show');
    Route::post('complaints/{complaint}', [CrmComplaintController::class, 'action'])->name('complaints.action');
    Route::get('forms', [CrmComplaintController::class, 'forms'])->name('forms');
    Route::post('forms', [CrmComplaintController::class, 'storeForm'])->name('forms.store');
    Route::get('forms/submissions/{submission}', [CrmComplaintController::class, 'submission'])->name('forms.submission');
    Route::post('forms/submissions/{submission}/review', [CrmComplaintController::class, 'reviewSubmission'])->name('forms.review');
});
