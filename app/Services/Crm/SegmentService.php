<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmSegment;
use App\Models\Patient;
use App\Models\PatientProblem;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Portal\PortalException;
use App\Services\Portal\PortalPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Groups of patients for campaigns (S11). The criteria are a fixed, short list — never free SQL. A criterion about a DIAGNOSIS is allowed only when the segment records its
 * documented legal basis AND a second person approves it, and even then only patients who gave the clinical-marketing consent are included (guideline override 7, class C4).
 */
class SegmentService
{
    public function __construct(private readonly PortalPolicy $policy, private readonly AuditLogger $audit) {}

    /** @param array<string, mixed> $criteria */
    public function create(int $organizationId, User $by, string $name, array $criteria, ?string $description = null, ?string $clinicalBasis = null): CrmSegment
    {
        $criteria = $this->clean($criteria);
        if (isset($criteria['problem_code_prefix']) && trim((string) $clinicalBasis) === '') {
            throw ValidationException::withMessages(['clinical_basis' => __('A group based on a diagnosis needs its documented legal basis.')]);
        }
        $segment = CrmSegment::create(['organization_id' => $organizationId, 'name' => mb_substr($name, 0, 120), 'description' => $description, 'criteria' => $criteria, 'clinical_basis' => $clinicalBasis ?: null, 'status' => 'draft', 'created_by' => $by->id]);
        $this->audit->log('CREATE', CrmSegment::class, $segment->id, null, ['criteria' => $criteria]);

        return $segment;
    }

    public function activate(CrmSegment $segment, User $by): CrmSegment
    {
        if (isset($segment->criteria['problem_code_prefix']) && $segment->created_by === $by->id) {
            throw new PortalException(__('A group based on a diagnosis must be approved by someone other than its author.'), 403);
        }
        $segment->update(['status' => 'active', 'approved_by' => $by->id, 'approved_at' => now(), 'last_count' => $this->count($segment->fresh()), 'last_counted_at' => now()]);
        $this->audit->log('UPDATE', CrmSegment::class, $segment->id, null, ['status' => 'active', 'by' => $by->id]);

        return $segment;
    }

    public function retire(CrmSegment $segment): CrmSegment
    {
        $segment->update(['status' => 'retired']);

        return $segment;
    }

    public function count(CrmSegment $segment): int
    {
        return $this->patients($segment)->count();
    }

    /** @return Builder<Patient> */
    public function patients(CrmSegment $segment): Builder
    {
        $c = (array) $segment->criteria;
        $q = Patient::query()->where('organization_id', $segment->organization_id)->where('status', 'active')->whereNull('deceased_at')->whereNull('merged_into_patient_id')->where('is_temporary', false)->where('is_unknown', false);

        if (isset($c['age_from'])) {
            $q->whereDate('date_of_birth', '<=', now()->subYears((int) $c['age_from'])->toDateString());
        }
        if (isset($c['age_to'])) {
            $q->whereDate('date_of_birth', '>', now()->subYears((int) $c['age_to'] + 1)->toDateString());
        }
        if (! empty($c['sex'])) {
            $q->where('sex', $c['sex']);
        }
        if (isset($c['has_portal'])) {
            $c['has_portal'] ? $q->whereHas('portalAccount', fn ($a) => $a->where('status', 'active')) : $q->whereDoesntHave('portalAccount', fn ($a) => $a->where('status', 'active'));
        }
        if (isset($c['branch_id'])) {
            $q->whereHas('branchRegistrations', fn ($b) => $b->where('branch_id', (int) $c['branch_id'])->where('status', 'active'));
        }
        if (! empty($c['language'])) {
            $q->whereHas('preference', fn ($p) => $p->where('preferred_language', $c['language']));
        }
        if (isset($c['visits_at_least'])) {
            $q->has('encounters', '>=', (int) $c['visits_at_least']);
        }
        if (isset($c['last_visit_within_days'])) {
            $q->whereHas('encounters', fn ($e) => $e->whereDate('encounter_date', '>=', now()->subDays((int) $c['last_visit_within_days'])->toDateString()));
        }
        if (isset($c['last_visit_before_days'])) {
            $cut = now()->subDays((int) $c['last_visit_before_days'])->toDateString();
            $q->whereHas('encounters')->whereDoesntHave('encounters', fn ($e) => $e->whereDate('encounter_date', '>', $cut));
        }
        if (isset($c['problem_code_prefix'])) {
            abort_if(trim((string) $segment->clinical_basis) === '', 403);
            $prefix = str_replace(['%', '_'], '', (string) $c['problem_code_prefix']);
            $q->whereIn('patients.id', PatientProblem::query()->where('status', 'active')->where('problem_code', 'like', $prefix.'%')->select('patient_id'))
                ->whereHas('consents', fn ($k) => $k->where('consent_type', 'clinical_marketing')->where('status', 'active'));
        }

        return $q;
    }

    /** @param array<string, mixed> $criteria @return array<string, mixed> */
    private function clean(array $criteria): array
    {
        $allowed = (array) $this->policy->get('crm.segment_criteria', []);
        $out = [];
        foreach ($criteria as $key => $value) {
            if (! in_array($key, $allowed, true)) {
                throw ValidationException::withMessages(['criteria' => __('Unknown condition: :k', ['k' => $key])]);
            }
            if ($value === null || $value === '') {
                continue;
            }
            $out[$key] = in_array($key, ['sex', 'language', 'problem_code_prefix'], true) ? (string) $value : (is_bool($value) || $key === 'has_portal' ? (bool) $value : (int) $value);
        }

        return $out;
    }
}
