<?php

use Illuminate\Support\Facades\Route;
use Modules\Ai\Http\Controllers\Web\AiAssistController;
use Modules\Ai\Http\Controllers\Web\AiGovernanceController;
use Modules\Ai\Http\Controllers\Web\AiHomeController;
use Modules\Ai\Http\Controllers\Web\AiOversightController;

// AI and clinical intelligence: /admin/ai/*. Using the assistant needs ai.use; governing it needs its own rights. Nothing here writes to a clinical record.
Route::prefix('ai')->name('ai.')->group(function () {
    Route::get('/', [AiHomeController::class, 'index'])->name('index');
    Route::post('start-local', [AiHomeController::class, 'startLocal'])->name('start');

    Route::get('summary', [AiAssistController::class, 'summary'])->name('summary');
    Route::post('summary', [AiAssistController::class, 'runSummary'])->name('summary.run');
    Route::get('notes', [AiAssistController::class, 'notes'])->name('notes');
    Route::post('notes', [AiAssistController::class, 'runNote'])->name('notes.run');
    Route::get('ask', [AiAssistController::class, 'ask'])->name('ask');
    Route::post('ask', [AiAssistController::class, 'runAsk'])->name('ask.run');
    Route::get('figures', [AiAssistController::class, 'figures'])->name('figures');
    Route::post('figures', [AiAssistController::class, 'runFigures'])->name('figures.run');
    Route::get('forecast', [AiAssistController::class, 'forecast'])->name('forecast');
    Route::post('forecast', [AiAssistController::class, 'runForecast'])->name('forecast.run');
    Route::get('claim-check', [AiAssistController::class, 'claim'])->name('claim');
    Route::post('claim-check', [AiAssistController::class, 'runClaim'])->name('claim.run');
    Route::get('reading-order', [AiAssistController::class, 'worklist'])->name('worklist');
    Route::post('reading-order', [AiAssistController::class, 'runWorklist'])->name('worklist.run');
    Route::get('medicines', [AiAssistController::class, 'medicines'])->name('medicines');
    Route::post('medicines', [AiAssistController::class, 'runMedicines'])->name('medicines.run');
    Route::get('early-warning', [AiAssistController::class, 'score'])->name('score');
    Route::post('early-warning', [AiAssistController::class, 'runScore'])->name('score.run');
    Route::get('outputs', [AiAssistController::class, 'outputs'])->name('outputs');
    Route::get('outputs/{output}', [AiAssistController::class, 'show'])->name('outputs.show');
    Route::post('outputs/{output}/accept', [AiAssistController::class, 'accept'])->name('outputs.accept');
    Route::post('outputs/{output}/reject', [AiAssistController::class, 'reject'])->name('outputs.reject');
    Route::post('outputs/{output}/feedback', [AiAssistController::class, 'feedback'])->name('outputs.feedback');

    Route::get('use-cases', [AiGovernanceController::class, 'useCases'])->name('usecases');
    Route::post('use-cases/{useCase}/setup', [AiGovernanceController::class, 'setup'])->name('usecases.setup');
    Route::post('use-cases/{useCase}/enable', [AiGovernanceController::class, 'enable'])->name('usecases.enable');
    Route::post('use-cases/{useCase}/pause', [AiGovernanceController::class, 'pause'])->name('usecases.pause');
    Route::post('use-cases/{useCase}/retire', [AiGovernanceController::class, 'retire'])->name('usecases.retire');
    Route::get('providers', [AiGovernanceController::class, 'providers'])->name('providers');
    Route::post('providers', [AiGovernanceController::class, 'addProvider'])->name('providers.store');
    Route::post('providers/{provider}/toggle', [AiGovernanceController::class, 'toggleProvider'])->name('providers.toggle');
    Route::post('providers/{provider}/legal', [AiGovernanceController::class, 'legal'])->name('providers.legal');
    Route::post('providers/{provider}/models', [AiGovernanceController::class, 'addModel'])->name('models.store');
    Route::post('models/{model}/evaluate', [AiGovernanceController::class, 'evaluate'])->whereNumber('model')->name('models.evaluate');
    Route::post('models/{model}/approve', [AiGovernanceController::class, 'approveModel'])->whereNumber('model')->name('models.approve');
    Route::post('models/{model}/retire', [AiGovernanceController::class, 'retireModel'])->whereNumber('model')->name('models.retire');
    Route::get('prompts', [AiGovernanceController::class, 'prompts'])->name('prompts');
    Route::post('prompts', [AiGovernanceController::class, 'draftPrompt'])->name('prompts.store');
    Route::post('prompts/{prompt}/activate', [AiGovernanceController::class, 'activatePrompt'])->whereNumber('prompt')->name('prompts.activate');
    Route::get('knowledge', [AiGovernanceController::class, 'knowledge'])->name('knowledge');
    Route::post('knowledge', [AiGovernanceController::class, 'addKnowledge'])->name('knowledge.store');
    Route::post('knowledge/{item}/approve', [AiGovernanceController::class, 'approveKnowledge'])->name('knowledge.approve');
    Route::post('knowledge/{item}/retire', [AiGovernanceController::class, 'retireKnowledge'])->name('knowledge.retire');
    Route::get('evaluations', [AiGovernanceController::class, 'evaluations'])->name('evaluations');

    Route::get('incidents', [AiOversightController::class, 'incidents'])->name('incidents');
    Route::post('incidents', [AiOversightController::class, 'report'])->name('incidents.store');
    Route::post('incidents/{incident}/assign', [AiOversightController::class, 'assign'])->name('incidents.assign');
    Route::post('incidents/{incident}/close', [AiOversightController::class, 'close'])->name('incidents.close');
    Route::get('monitoring', [AiOversightController::class, 'monitoring'])->name('monitoring');
    Route::get('audit', [AiOversightController::class, 'audit'])->name('audit');
    Route::post('kill-switch', [AiOversightController::class, 'killSwitch'])->name('killswitch');
});
