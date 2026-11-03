<?php

namespace App\Services\Quality;

use App\Models\Quality\QaMortalityReview;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every death in the hospital gets a review, once, so that learning does not depend on who remembers. Deaths are found in the emergency department and the wards;
 * a reviewer classifies each and writes what could be learned. The review is about the care system, not about a clinician.
 */
class MortalityService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly QualityPolicy $policy, private readonly QualityAlerts $alerts, private readonly AuditLogger $audit) {}

    /** Open a review for every death not yet seen. @return int new reviews */
    public function sweep(int $organizationId): int
    {
        $n = 0;
        $ed = DB::table('ed_visits as v')->join('patients as p', 'p.id', '=', 'v.patient_id')->where('v.organization_id', $organizationId)->where('v.disposition_type', 'death')
            ->select('v.id', 'v.patient_id', 'v.branch_id', 'v.dispositioned_at', 'p.deceased_at')->orderBy('v.id')->get();
        foreach ($ed as $r) {
            $n += $this->open($organizationId, 'ed_visit', (int) $r->id, (int) $r->patient_id, $r->branch_id, $r->deceased_at ?? $r->dispositioned_at);
        }
        $ward = DB::table('ipd_admissions as a')->join('patients as p', 'p.id', '=', 'a.patient_id')->where('a.organization_id', $organizationId)
            ->where(fn ($q) => $q->where('a.status', 'deceased')->orWhere(fn ($w) => $w->whereNotNull('p.deceased_at')->whereColumn('p.deceased_at', '>=', 'a.admitted_at')->where(fn ($d) => $d->whereNull('a.actual_discharge_date')->orWhereRaw('date(p.deceased_at) <= date(a.actual_discharge_date)'))))
            ->select('a.id', 'a.patient_id', 'a.branch_id', 'a.department_id', 'a.updated_at', 'p.deceased_at')->orderBy('a.id')->get();
        foreach ($ward as $r) {
            $n += $this->open($organizationId, 'ipd_admission', (int) $r->id, (int) $r->patient_id, $r->branch_id, $r->deceased_at ?? $r->updated_at, $r->department_id);
        }

        return $n;
    }

    private function open(int $org, string $type, int $id, int $patientId, mixed $branchId, mixed $diedAt, mixed $departmentId = null): int
    {
        if (QaMortalityReview::query()->where(['organization_id' => $org, 'source_type' => $type, 'source_id' => $id])->exists()) {
            return 0;
        }
        if (CarbonImmutable::parse((string) $diedAt, 'UTC')->isAfter(now())) {
            return 0;
        }
        $review = QaMortalityReview::create(['organization_id' => $org, 'branch_id' => $branchId, 'number' => $this->numbers->next('qa.mortality', $org, null, prefix: 'MR', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR),
            'patient_id' => $patientId, 'source_type' => $type, 'source_id' => $id, 'department_id' => $departmentId, 'died_at' => CarbonImmutable::parse((string) $diedAt, 'UTC'), 'status' => 'pending']);
        $this->audit->log('CREATE', QaMortalityReview::class, $review->id, null, ['number' => $review->number]);

        return 1;
    }

    public function assign(QaMortalityReview $review, User $reviewer, User $by): QaMortalityReview
    {
        if ($review->status !== 'pending') {
            throw ValidationException::withMessages(['status' => __('This review has been done.')]);
        }
        $review->update(['reviewer_id' => $reviewer->id]);
        $this->alerts->toUsers($review->organization_id, [$reviewer->id], 'info', __('You are asked to review a death: :n.', ['n' => $review->number]), QaMortalityReview::class, $review->id, 'qa-mr-'.$review->id);

        return $review;
    }

    public function review(QaMortalityReview $review, User $by, string $classification, string $findings, ?string $learning): QaMortalityReview
    {
        if ($review->status !== 'pending') {
            throw ValidationException::withMessages(['status' => __('This review has been done.')]);
        }
        if (! in_array($classification, $this->policy->list('mortality.classifications'), true)) {
            throw ValidationException::withMessages(['classification' => __('Choose a classification.')]);
        }
        if (mb_strlen(trim($findings)) < 20) {
            throw ValidationException::withMessages(['findings' => __('Write what the review found.')]);
        }
        $review->update(['status' => 'reviewed', 'reviewer_id' => $review->reviewer_id ?: $by->id, 'reviewed_at' => now(), 'classification' => $classification, 'findings' => mb_substr(strip_tags($findings), 0, 6000), 'learning' => $learning ? mb_substr(strip_tags($learning), 0, 1000) : null]);
        $this->audit->log('UPDATE', QaMortalityReview::class, $review->id, null, ['classification' => $classification]);
        if (in_array($classification, ['potentially_preventable', 'preventable'], true)) {
            $this->alerts->raise($review->organization_id, 'warning', __('A death was judged :c: :n.', ['c' => str_replace('_', ' ', $classification), 'n' => $review->number]), QaMortalityReview::class, $review->id, 'qa-mr-prev-'.$review->id, 'quality.mortality.view', false, $review->branch_id);
        }

        return $review;
    }
}
