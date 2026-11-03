<?php

namespace App\Services\Ai;

use App\Models\Ai\AiEvalRun;
use App\Models\Ai\AiModel;
use App\Models\Ai\AiProvider;
use App\Models\Ai\AiUseCase;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * The register of what AI is used for. A use case has an owner, an intended use, a risk class, a data class, a provider and an approved model. It is switched on by someone with the right (a different person than
 * the one who registered it when the risk is above low), only if it is built, and it can be paused at once by anyone with the kill-switch right. Planned use cases that are not built cannot be switched on.
 */
class UseCaseService
{
    public const CATALOGUE = [
        ['timeline_summary', 'Patient timeline summary', 'Summarise a patient\'s record from the facts already in it, so a clinician can read it faster. It states nothing that is not in the record.', 'low', true],
        ['note_draft', 'Clinical note draft', 'Lay out the facts of a visit as a draft note. The clinician writes the assessment and plan and decides what goes into the record.', 'low', true],
        ['knowledge_answer', 'Hospital knowledge answers', 'Answer staff questions from the hospital\'s approved policies and procedures, quoting and citing them.', 'low', true],
        ['inventory_forecast', 'Inventory demand forecast', 'Forecast how much of an item will be used, with its range and how wrong past forecasts were. Advisory; nothing is ordered from it.', 'low', true],
        ['nl_analytics', 'Natural-language analytics', 'Turn a plain question about the hospital\'s figures into a query that can be read and checked, and show the result.', 'low', true],
        ['medication_review', 'Medication review support', 'Lay a patient\'s medicines, allergies, the pharmacy\'s own safety alerts and plain counts side by side so a pharmacist can review them. It states facts only: it never says to start, stop or change anything.', 'high', true],
        ['lab_interpretation', 'Laboratory interpretation support', 'Planned. Would explain patterns in results. Not built.', 'high', false],
        ['icu_early_warning', 'ICU early-warning support', 'Planned. Would highlight deterioration trends. Not built; a risk score.', 'high', false],
        ['ed_triage_support', 'Emergency triage support', 'Planned. Would suggest triage notes. Never assigns triage. Not built.', 'high', false],
        ['radiology_worklist', 'Radiology worklist support', 'Suggest the order in which finished studies could be read, by priority and then by how long they have waited, and say why. The real worklist and priorities are not changed.', 'medium', true],
        ['insurance_claim_check', 'Insurance claim pre-check', 'Restate the checks the Insurance module runs on a claim, with the documents, queries and deadline, so staff can see what may hold it up. It never decides, submits or changes a claim.', 'medium', true],
        ['portal_assistant', 'Patient portal assistant', 'Planned. Would answer patients\' questions about the hospital. Not built; free text from patients needs the injection tests first.', 'medium', false],
        ['risk_scoring', 'Clinical risk scores', 'Work out an early-warning screen (NEWS2) from the latest stored vital signs, showing every input, its points and what could not be scored. It is a screen, not a diagnosis, and it triggers nothing.', 'high', true],
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /** Put the catalogue in the register (all planned; the built ones can then be switched on). Safe to repeat. @return int added */
    public function installCatalogue(int $organizationId): int
    {
        $n = 0;
        foreach (self::CATALOGUE as [$code, $name, $use, $risk, $built]) {
            $level = in_array($code, ['knowledge_answer', 'inventory_forecast', 'nl_analytics'], true) ? 'C2' : ($code === 'radiology_worklist' ? 'C3' : 'C4');
            $uc = AiUseCase::query()->firstOrCreate(['organization_id' => $organizationId, 'code' => $code], ['name' => $name, 'intended_use' => $use, 'risk_class' => $risk, 'data_level' => $level, 'status' => 'planned', 'implemented' => $built]);
            $uc->wasRecentlyCreated && $n++;
            // A function that was only planned when this hospital was set up and has been built since: bring its description up to date. It stays off.
            if (! $uc->wasRecentlyCreated && $built && ! $uc->implemented && $uc->status === 'planned') {
                $uc->update(['implemented' => true, 'name' => $name, 'intended_use' => $use, 'risk_class' => $risk, 'data_level' => $level]);
            }
        }

        return $n;
    }

    public function setup(AiUseCase $uc, ?User $owner, ?AiProvider $provider, ?AiModel $model): AiUseCase
    {
        if ($provider && $provider->organization_id !== $uc->organization_id || $model && ($model->organization_id !== $uc->organization_id || ($provider && $model->provider_id !== $provider->id))) {
            throw ValidationException::withMessages(['provider_id' => __('That provider or model does not belong here.')]);
        }
        $uc->update(['owner_id' => $owner->id ?? $uc->owner_id, 'provider_id' => $provider->id ?? $uc->provider_id, 'model_id' => $model->id ?? $uc->model_id, 'registered_by' => $uc->registered_by]);
        $this->audit->log('UPDATE', AiUseCase::class, $uc->id, null, ['owner' => $uc->owner_id, 'provider' => $uc->provider_id, 'model' => $uc->model_id]);

        return $uc;
    }

    public function enable(AiUseCase $uc, User $by): AiUseCase
    {
        if (! $uc->implemented) {
            throw ValidationException::withMessages(['status' => __('This function is not built, so it cannot be switched on.')]);
        }
        if ($uc->status === 'retired') {
            throw ValidationException::withMessages(['status' => __('A retired function cannot be switched on.')]);
        }
        if (! $uc->owner_id || mb_strlen(trim($uc->intended_use)) < 20) {
            throw ValidationException::withMessages(['status' => __('Name an owner and state the intended use first.')]);
        }
        $model = $uc->model;
        if (! $uc->provider || ! $model || $model->status !== 'approved' || $model->provider_id !== $uc->provider_id) {
            throw ValidationException::withMessages(['status' => __('It needs a provider and a model that has passed its evaluation.')]);
        }
        if ($uc->risk_class !== 'low' && $uc->registered_by === $by->id) {
            throw ValidationException::withMessages(['status' => __('A second person must approve a function that is not low-risk.')]);
        }
        if ($uc->risk_class !== 'low' && ! AiEvalRun::query()->where('model_id', $model->id)->where('passed', true)->whereHas('set', fn ($q) => $q->where('use_case_code', $uc->code))->exists()) {
            throw ValidationException::withMessages(['status' => __('Above low risk, this function needs an evaluation of its own that this model has passed.')]);
        }
        $uc->update(['status' => 'enabled', 'approved_by' => $by->id, 'approved_at' => now(), 'paused_reason' => null]);
        $this->audit->log('UPDATE', AiUseCase::class, $uc->id, null, ['status' => 'enabled']);

        return $uc;
    }

    /** The kill switch: stops this function now. */
    public function pause(AiUseCase $uc, User $by, string $reason): AiUseCase
    {
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why it is paused.')]);
        }
        $uc->update(['status' => 'paused', 'paused_reason' => mb_substr($reason, 0, 300)]);
        $this->audit->log('UPDATE', AiUseCase::class, $uc->id, null, ['status' => 'paused', 'reason' => $uc->paused_reason]);

        return $uc;
    }

    public function retire(AiUseCase $uc, User $by, string $reason): AiUseCase
    {
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why it is retired.')]);
        }
        $uc->update(['status' => 'retired', 'paused_reason' => mb_substr($reason, 0, 300)]);
        $this->audit->log('UPDATE', AiUseCase::class, $uc->id, null, ['status' => 'retired']);

        return $uc;
    }
}
