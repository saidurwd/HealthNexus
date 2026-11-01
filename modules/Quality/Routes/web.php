<?php

use Illuminate\Support\Facades\Route;
use Modules\Quality\Http\Controllers\Web\ActionController;
use Modules\Quality\Http\Controllers\Web\DocumentController;
use Modules\Quality\Http\Controllers\Web\GovernanceController;
use Modules\Quality\Http\Controllers\Web\IncidentController;
use Modules\Quality\Http\Controllers\Web\InfectionController;
use Modules\Quality\Http\Controllers\Web\ProgrammeController;
use Modules\Quality\Http\Controllers\Web\QualityHomeController;

// Quality, safety and compliance: /admin/quality/*. Every staff member can report an incident and read documents; the rest needs a quality right.
Route::prefix('quality')->name('quality.')->group(function () {
    Route::get('/', [QualityHomeController::class, 'index'])->name('index');

    Route::get('incidents', [IncidentController::class, 'index'])->name('incidents');
    Route::get('incidents/new', [IncidentController::class, 'create'])->name('incidents.create');
    Route::post('incidents', [IncidentController::class, 'store'])->name('incidents.store');
    Route::get('incidents/mine', [IncidentController::class, 'mine'])->name('incidents.mine');
    Route::get('incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::post('incidents/{incident}/triage', [IncidentController::class, 'triage'])->name('incidents.triage');
    Route::post('incidents/{incident}/amend', [IncidentController::class, 'amend'])->name('incidents.amend');
    Route::post('incidents/{incident}/close', [IncidentController::class, 'close'])->name('incidents.close');
    Route::post('incidents/{incident}/reopen', [IncidentController::class, 'reopen'])->name('incidents.reopen');
    Route::post('incidents/{incident}/reveal', [IncidentController::class, 'reveal'])->name('incidents.reveal');
    Route::post('incidents/{incident}/investigation', [IncidentController::class, 'startInvestigation'])->name('investigation.start');
    Route::post('investigations/{investigation}/save', [IncidentController::class, 'saveInvestigation'])->name('investigation.save');
    Route::post('investigations/{investigation}/submit', [IncidentController::class, 'submitInvestigation'])->name('investigation.submit');
    Route::post('investigations/{investigation}/review', [IncidentController::class, 'reviewInvestigation'])->name('investigation.review');

    Route::get('actions', [ActionController::class, 'index'])->name('actions');
    Route::post('actions', [ActionController::class, 'store'])->name('actions.store');
    Route::post('actions/{action}/start', [ActionController::class, 'start'])->name('actions.start');
    Route::post('actions/{action}/complete', [ActionController::class, 'complete'])->name('actions.complete');
    Route::post('actions/{action}/verify', [ActionController::class, 'verify'])->name('actions.verify');
    Route::post('actions/{action}/cancel', [ActionController::class, 'cancel'])->name('actions.cancel');

    Route::get('infection', [InfectionController::class, 'index'])->name('infection');
    Route::post('infection/cases', [InfectionController::class, 'storeCase'])->name('infection.cases.store');
    Route::post('infection/cases/{case}/decide', [InfectionController::class, 'decide'])->name('infection.cases.decide');
    Route::post('infection/isolations', [InfectionController::class, 'isolate'])->name('infection.isolations.store');
    Route::post('infection/isolations/{isolation}/end', [InfectionController::class, 'endIsolation'])->name('infection.isolations.end');
    Route::post('infection/hand-hygiene', [InfectionController::class, 'handHygiene'])->name('infection.hh.store');
    Route::post('infection/outbreaks/{outbreak}/close', [InfectionController::class, 'closeOutbreak'])->name('infection.outbreaks.close');

    Route::get('indicators', [ProgrammeController::class, 'indicators'])->name('indicators');
    Route::post('indicators/recompute', [ProgrammeController::class, 'recompute'])->name('indicators.recompute');
    Route::post('indicators/{indicator}/target', [ProgrammeController::class, 'setTarget'])->name('indicators.target');
    Route::get('audits', [ProgrammeController::class, 'audits'])->name('audits');
    Route::post('audits', [ProgrammeController::class, 'storeAudit'])->name('audits.store');
    Route::get('audits/{audit}', [ProgrammeController::class, 'audit'])->name('audits.show');
    Route::post('audits/{audit}/record', [ProgrammeController::class, 'recordAudit'])->name('audits.record');
    Route::post('audits/{audit}/complete', [ProgrammeController::class, 'completeAudit'])->name('audits.complete');
    Route::get('mortality', [ProgrammeController::class, 'mortality'])->name('mortality');
    Route::post('mortality/find', [ProgrammeController::class, 'findDeaths'])->name('mortality.find');
    Route::get('mortality/{review}', [ProgrammeController::class, 'review'])->name('mortality.show');
    Route::post('mortality/{review}/assign', [ProgrammeController::class, 'assignReview'])->name('mortality.assign');
    Route::post('mortality/{review}/review', [ProgrammeController::class, 'doReview'])->name('mortality.review');

    Route::get('documents', [DocumentController::class, 'index'])->name('documents');
    Route::get('documents/new', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/to-read', [DocumentController::class, 'toRead'])->name('documents.toread');
    Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::post('documents/{document}/acknowledge', [DocumentController::class, 'acknowledge'])->name('documents.acknowledge');
    Route::post('documents/{document}/version', [DocumentController::class, 'newVersion'])->name('documents.version');
    Route::post('documents/{document}/assign', [DocumentController::class, 'assign'])->name('documents.assign');
    Route::post('documents/{document}/retire', [DocumentController::class, 'retire'])->name('documents.retire');
    Route::post('document-versions/{version}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::post('document-versions/{version}/submit', [DocumentController::class, 'submit'])->name('documents.submit');
    Route::post('document-versions/{version}/decide', [DocumentController::class, 'decide'])->name('documents.decide');

    Route::get('privileges', [GovernanceController::class, 'privileges'])->name('privileges');
    Route::post('privileges/catalog', [GovernanceController::class, 'saveCatalog'])->name('privileges.catalog');
    Route::post('privileges', [GovernanceController::class, 'requestPrivilege'])->name('privileges.request');
    Route::post('privileges/{privilege}/decide', [GovernanceController::class, 'decidePrivilege'])->name('privileges.decide');
    Route::post('privileges/{privilege}/withdraw', [GovernanceController::class, 'withdrawPrivilege'])->name('privileges.withdraw');
    Route::get('compliance', [GovernanceController::class, 'compliance'])->name('compliance');
    Route::post('compliance', [GovernanceController::class, 'storeCompliance'])->name('compliance.store');
    Route::post('compliance/{item}/done', [GovernanceController::class, 'completeCompliance'])->name('compliance.done');
    Route::get('accreditation', [GovernanceController::class, 'accreditation'])->name('accreditation');
    Route::post('accreditation/assessments', [GovernanceController::class, 'startAssessment'])->name('accreditation.start');
    Route::get('accreditation/{assessment}', [GovernanceController::class, 'assessment'])->name('accreditation.show');
    Route::post('accreditation/{assessment}/record/{result}', [GovernanceController::class, 'recordResult'])->whereNumber('result')->name('accreditation.record');
    Route::post('accreditation/{assessment}/gap/{result}', [GovernanceController::class, 'gapAction'])->whereNumber('result')->name('accreditation.gap');
    Route::post('accreditation/{assessment}/complete', [GovernanceController::class, 'completeAssessment'])->name('accreditation.complete');
    Route::get('committees', [GovernanceController::class, 'committees'])->name('committees');
    Route::post('committees/meetings', [GovernanceController::class, 'scheduleMeeting'])->name('meetings.store');
    Route::get('meetings/{meeting}', [GovernanceController::class, 'meeting'])->name('meetings.show');
    Route::post('meetings/{meeting}/items', [GovernanceController::class, 'addItem'])->name('meetings.items');
    Route::post('meetings/{meeting}/hold', [GovernanceController::class, 'hold'])->name('meetings.hold');
    Route::post('meetings/{meeting}/items/{item}', [GovernanceController::class, 'recordItem'])->whereNumber('item')->name('meetings.record');
    Route::post('meetings/{meeting}/minutes', [GovernanceController::class, 'minute'])->name('meetings.minute');
    Route::get('complaints', [GovernanceController::class, 'complaints'])->name('complaints');
    Route::post('complaints/{complaint}/raise', [GovernanceController::class, 'raiseComplaint'])->name('complaints.raise');
});
