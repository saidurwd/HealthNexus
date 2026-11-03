<?php

use Illuminate\Support\Facades\Route;
use Modules\Insurance\Http\Controllers\Web\InsuranceClaimController;
use Modules\Insurance\Http\Controllers\Web\InsuranceDeskController;
use Modules\Insurance\Http\Controllers\Web\InsuranceMoneyController;

Route::prefix('insurance')->name('insurance.')->group(function () {
    Route::get('/', [InsuranceDeskController::class, 'dashboard'])->name('dashboard');
    Route::get('payers', [InsuranceDeskController::class, 'payers'])->name('payers');
    Route::post('payers', [InsuranceDeskController::class, 'storePayer'])->name('payers.store');
    Route::post('payers/{payer}/plans', [InsuranceDeskController::class, 'storePlan'])->name('plans.store');
    Route::get('policies', [InsuranceDeskController::class, 'policies'])->name('policies');
    Route::post('policies', [InsuranceDeskController::class, 'storePolicy'])->name('policies.store');
    Route::post('policies/{policy}/verify', [InsuranceDeskController::class, 'verify'])->name('policies.verify');
    Route::get('preauths', [InsuranceDeskController::class, 'preauths'])->name('preauths');
    Route::post('preauths', [InsuranceDeskController::class, 'requestPreauth'])->name('preauths.store');
    Route::post('preauths/{preauth}/decide', [InsuranceDeskController::class, 'decidePreauth'])->name('preauths.decide');
    Route::get('worklist', [InsuranceDeskController::class, 'worklist'])->name('worklist');

    Route::get('claims', [InsuranceClaimController::class, 'index'])->name('claims.index');
    Route::post('claims', [InsuranceClaimController::class, 'store'])->name('claims.store');
    Route::post('claims/export', [InsuranceClaimController::class, 'export'])->name('claims.export');
    Route::get('claims/{claim}', [InsuranceClaimController::class, 'show'])->name('claims.show');
    Route::post('claims/{claim}/ready', [InsuranceClaimController::class, 'ready'])->name('claims.ready');
    Route::post('claims/{claim}/rebuild', [InsuranceClaimController::class, 'rebuild'])->name('claims.rebuild');
    Route::post('claims/{claim}/documents/{document}', [InsuranceClaimController::class, 'document'])->name('claims.document');
    Route::post('claims/{claim}/version', [InsuranceClaimController::class, 'newVersion'])->name('claims.version');
    Route::post('claims/{claim}/secondary', [InsuranceClaimController::class, 'secondary'])->name('claims.secondary');
    Route::post('claims/{claim}/cancel', [InsuranceClaimController::class, 'cancel'])->name('claims.cancel');
    Route::post('claims/{claim}/reply', [InsuranceClaimController::class, 'raise'])->name('claims.reply');
    Route::post('claims/{claim}/appeal', [InsuranceClaimController::class, 'appeal'])->name('claims.appeal');
    Route::post('claims/{claim}/writeoff', [InsuranceMoneyController::class, 'requestWriteoff'])->name('claims.writeoff');
    Route::post('queries/{query}/answer', [InsuranceClaimController::class, 'answer'])->name('queries.answer');
    Route::post('appeals/{appeal}/decide', [InsuranceClaimController::class, 'decideAppeal'])->name('appeals.decide');
    Route::get('submissions/{submission}/download', [InsuranceClaimController::class, 'download'])->name('submissions.download');
    Route::post('submissions/{submission}/submitted', [InsuranceClaimController::class, 'markSubmitted'])->name('submissions.submitted');

    Route::get('remittances', [InsuranceMoneyController::class, 'remittances'])->name('remittances.index');
    Route::post('remittances', [InsuranceMoneyController::class, 'storeRemittance'])->name('remittances.store');
    Route::get('remittances/{remittance}', [InsuranceMoneyController::class, 'showRemittance'])->name('remittances.show');
    Route::post('remittances/{remittance}/allocate', [InsuranceMoneyController::class, 'allocate'])->name('remittances.allocate');
    Route::post('writeoffs/{writeoff}/decide', [InsuranceMoneyController::class, 'decideWriteoff'])->name('writeoffs.decide');

    Route::get('corporate', [InsuranceMoneyController::class, 'corporates'])->name('corporate');
    Route::post('corporate/batches', [InsuranceMoneyController::class, 'createBatch'])->name('corporate.batches.store');
    Route::post('corporate/batches/{batch}/issue', [InsuranceMoneyController::class, 'issueBatch'])->name('corporate.batches.issue');
    Route::post('corporate/batches/{batch}/pay', [InsuranceMoneyController::class, 'payBatch'])->name('corporate.batches.pay');
});
