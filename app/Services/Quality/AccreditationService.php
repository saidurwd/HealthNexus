<?php

namespace App\Services\Quality;

use App\Models\Quality\QaAction;
use App\Models\Quality\QaAssessment;
use App\Models\Quality\QaAssessmentResult;
use App\Models\Quality\QaStandard;
use App\Models\Quality\QaStandardItem;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Accreditation and licensing checklists. A standard is the hospital's own list of requirements (the accrediting body's text is the hospital's to enter); an assessment goes through it item by item.
 * Every gap needs an action before the assessment can be completed.
 */
class AccreditationService
{
    public const RESULTS = ['not_assessed', 'compliant', 'partial', 'non_compliant', 'na'];

    public function __construct(private readonly ActionService $actions, private readonly AuditLogger $audit) {}

    /** @param list<array{code:string,chapter?:string,requirement:string}> $items */
    public function addStandard(int $organizationId, string $code, string $name, ?string $edition, array $items): QaStandard
    {
        $code = strtoupper(trim($code));
        if ($code === '' || mb_strlen(trim($name)) < 3 || $items === []) {
            throw ValidationException::withMessages(['name' => __('Give the standard a code, a name and its requirements.')]);
        }

        return DB::transaction(function () use ($organizationId, $code, $name, $edition, $items) {
            $standard = QaStandard::query()->updateOrCreate(['organization_id' => $organizationId, 'code' => $code], ['name' => mb_substr(trim($name), 0, 200), 'edition' => $edition ? mb_substr($edition, 0, 40) : null, 'active' => true]);
            foreach ($items as $i => $item) {
                QaStandardItem::query()->updateOrCreate(['standard_id' => $standard->id, 'code' => (string) $item['code']], ['chapter' => $item['chapter'] ?? null, 'requirement' => mb_substr((string) $item['requirement'], 0, 600), 'sort' => $i]);
            }

            return $standard;
        });
    }

    public function start(int $organizationId, QaStandard $standard, string $name, User $lead, ?int $branchId = null): QaAssessment
    {
        if ($standard->organization_id !== $organizationId) {
            abort(404);
        }

        return DB::transaction(function () use ($organizationId, $standard, $name, $lead, $branchId) {
            $a = QaAssessment::create(['organization_id' => $organizationId, 'branch_id' => $branchId, 'standard_id' => $standard->id, 'name' => mb_substr(trim($name) ?: $standard->name, 0, 200), 'status' => 'in_progress', 'started_on' => today()->toDateString(), 'lead_id' => $lead->id]);
            foreach ($standard->items as $item) {
                QaAssessmentResult::create(['assessment_id' => $a->id, 'item_id' => $item->id, 'result' => 'not_assessed']);
            }
            $this->audit->log('CREATE', QaAssessment::class, $a->id, null, ['standard' => $standard->code]);

            return $a;
        });
    }

    public function record(QaAssessment $a, QaAssessmentResult $r, User $by, string $result, ?string $evidence): QaAssessmentResult
    {
        $this->open($a);
        if ($r->assessment_id !== $a->id || ! in_array($result, self::RESULTS, true)) {
            throw ValidationException::withMessages(['result' => __('Choose a result.')]);
        }
        if (in_array($result, ['compliant', 'partial', 'non_compliant'], true) && mb_strlen(trim((string) $evidence)) < 5) {
            throw ValidationException::withMessages(['evidence' => __('Say what you saw (the evidence).')]);
        }
        $r->update(['result' => $result, 'evidence' => $evidence ? mb_substr(strip_tags($evidence), 0, 1000) : null, 'assessed_by' => $by->id, 'assessed_at' => now()]);

        return $r;
    }

    /** Raise the action that closes a gap and tie it to the item. */
    public function gapAction(QaAssessment $a, QaAssessmentResult $r, array $d, User $by): QaAction
    {
        $this->open($a);
        if (! in_array($r->result, ['partial', 'non_compliant'], true) || $r->assessment_id !== $a->id) {
            throw ValidationException::withMessages(['result' => __('Only a gap needs an action.')]);
        }
        $action = $this->actions->create($a->organization_id, ['source_type' => 'assessment', 'source_id' => $a->id, 'action_type' => 'corrective', 'branch_id' => $a->branch_id,
            'description' => ($d['description'] ?? '') ?: __('Close the gap in :c', ['c' => $r->item->code]), 'owner_id' => $d['owner_id'] ?? null, 'due_on' => $d['due_on'] ?? null], $by);
        $r->update(['action_id' => $action->id]);

        return $action;
    }

    public function score(QaAssessment $a): ?float
    {
        $rows = $a->results()->whereNotIn('result', ['na', 'not_assessed'])->pluck('result');
        if ($rows->isEmpty()) {
            return null;
        }

        return round(100 * ($rows->filter(fn ($r) => $r === 'compliant')->count() + 0.5 * $rows->filter(fn ($r) => $r === 'partial')->count()) / $rows->count(), 2);
    }

    public function complete(QaAssessment $a, User $by): QaAssessment
    {
        $this->open($a);
        $left = $a->results()->where('result', 'not_assessed')->count();
        if ($left > 0) {
            throw ValidationException::withMessages(['status' => __(':n item(s) have not been assessed.', ['n' => $left])]);
        }
        $open = $a->results()->whereIn('result', ['partial', 'non_compliant'])->whereNull('action_id')->count();
        if ($open > 0) {
            throw ValidationException::withMessages(['status' => __(':n gap(s) have no action.', ['n' => $open])]);
        }
        $a->update(['status' => 'completed', 'completed_on' => today()->toDateString(), 'score' => $this->score($a)]);
        $this->audit->log('UPDATE', QaAssessment::class, $a->id, null, ['completed' => true, 'score' => $a->score]);

        return $a;
    }

    private function open(QaAssessment $a): void
    {
        if ($a->status === 'completed') {
            throw ValidationException::withMessages(['status' => __('This assessment is complete.')]);
        }
    }
}
