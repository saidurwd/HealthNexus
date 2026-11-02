<?php

namespace App\Services\Welfare;

use App\Models\User;
use App\Models\Welfare\WfApplication;
use App\Models\Welfare\WfAssessment;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfGrant;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A grant is the committee's promise: up to this much, from this fund, for this patient, until this date. It can only be requested from an approved assessment and approved by a different person, against what the fund
 * has free. Approving sets the money aside (it counts as committed); it is spent only when Billing approves the discount (ApplicationService).
 */
class GrantService
{
    public function __construct(private readonly WelfarePolicy $policy, private readonly FundService $funds, private readonly NumberSeriesService $numbers, private readonly AuditLogger $audit) {}

    public function request(WfAssessment $a, WfFund $fund, mixed $cap, ?int $percent, ?string $purpose, User $by): WfGrant
    {
        if ($a->status !== 'approved' || ! $a->valid_until || $a->valid_until->lt(today())) {
            throw ValidationException::withMessages(['assessment' => __('The assessment must be approved and still in force.')]);
        }
        if ($a->category === 'none') {
            throw ValidationException::withMessages(['assessment' => __('This assessment did not find a need for a waiver.')]);
        }
        if ($fund->organization_id !== $a->organization_id || $fund->status !== 'active') {
            throw ValidationException::withMessages(['fund_id' => __('Choose an open fund of this hospital.')]);
        }
        if (! is_numeric($cap) || (float) $cap <= 0) {
            throw ValidationException::withMessages(['cap_amount' => __('Enter the most this grant may give.')]);
        }
        $cap = number_format((float) $cap, 2, '.', '');
        if ($fund->per_grant_cap !== null && bccomp($cap, (string) $fund->per_grant_cap, 2) > 0) {
            throw ValidationException::withMessages(['cap_amount' => __('This fund gives at most :c to one patient.', ['c' => $fund->per_grant_cap])]);
        }
        $percent ??= (int) $a->suggested_percent;
        if ($percent < 1 || $percent > 100) {
            throw ValidationException::withMessages(['percent' => __('Enter a share of the bill between 1 and 100.')]);
        }
        if (WfGrant::query()->where('patient_id', $a->patient_id)->where(fn ($q) => $q->where('status', 'requested')->orWhere(fn ($w) => $w->where('status', 'approved')->whereDate('valid_to', '>=', today())))->exists()) {
            throw ValidationException::withMessages(['assessment' => __('This patient already has a grant waiting or in force.')]);
        }

        return DB::transaction(function () use ($a, $fund, $cap, $percent, $purpose, $by) {
            $g = WfGrant::create(['organization_id' => $a->organization_id, 'number' => $this->numbers->next('wf.grant', (int) $a->organization_id, prefix: 'WG', format: '{PREFIX}-{YEAR}-{SEQ:6}', period: NumberSeriesService::PERIOD_YEAR, userId: $by->id),
                'patient_id' => $a->patient_id, 'assessment_id' => $a->id, 'fund_id' => $fund->id, 'cap_amount' => $cap, 'percent' => $percent, 'valid_from' => today(), 'valid_to' => today()->addDays($this->policy->int('grant_valid_days', 90)),
                'purpose' => $purpose ? mb_substr($purpose, 0, 200) : null, 'status' => 'requested', 'requested_by' => $by->id]);
            $this->audit->log('CREATE', WfGrant::class, $g->id, null, ['patient' => $a->patient_id, 'fund' => $fund->id, 'cap' => $cap]);

            return $g;
        });
    }

    public function approve(WfGrant $g, User $by, ?string $note = null): WfGrant
    {
        return DB::transaction(function () use ($g, $by, $note) {
            $fund = WfFund::query()->lockForUpdate()->findOrFail($g->fund_id);
            $g = WfGrant::query()->lockForUpdate()->findOrFail($g->id);
            if ($g->status !== 'requested') {
                throw ValidationException::withMessages(['grant' => __('This grant is not waiting for a decision.')]);
            }
            if ($g->requested_by === $by->id) {
                throw ValidationException::withMessages(['grant' => __('A second person must approve: the requester cannot approve their own grant.')]);
            }
            if ($fund->status !== 'active') {
                throw ValidationException::withMessages(['grant' => __('The fund is closed.')]);
            }
            if (bccomp($this->funds->available($fund), (string) $g->cap_amount, 2) < 0) {
                throw ValidationException::withMessages(['grant' => __('The fund has only :a free of other approved grants.', ['a' => $this->funds->available($fund)])]);
            }
            $assessment = WfAssessment::query()->findOrFail($g->assessment_id);
            $until = $assessment->valid_until && $assessment->valid_until->lt($g->valid_to) ? $assessment->valid_until : $g->valid_to;
            $g->update(['status' => 'approved', 'approved_by' => $by->id, 'approved_at' => now(), 'valid_from' => today(), 'valid_to' => $until, 'decision_note' => $note ? mb_substr($note, 0, 300) : null]);
            $this->audit->log('UPDATE', WfGrant::class, $g->id, null, ['status' => 'approved']);

            return $g;
        });
    }

    public function reject(WfGrant $g, User $by, string $note): WfGrant
    {
        if ($g->status !== 'requested') {
            throw ValidationException::withMessages(['grant' => __('This grant is not waiting for a decision.')]);
        }
        if (mb_strlen(trim($note)) < 5) {
            throw ValidationException::withMessages(['note' => __('Say why it is turned down.')]);
        }
        $g->update(['status' => 'rejected', 'approved_by' => $by->id, 'approved_at' => now(), 'decision_note' => mb_substr($note, 0, 300)]);
        $this->audit->log('UPDATE', WfGrant::class, $g->id, null, ['status' => 'rejected']);

        return $g;
    }

    /** Stops a grant that is waiting or in force; what was already given stays given. A grant with a discount still waiting for Billing cannot be stopped. */
    public function cancel(WfGrant $g, User $by, string $reason): WfGrant
    {
        if (! in_array($g->status, ['requested', 'approved'], true)) {
            throw ValidationException::withMessages(['grant' => __('This grant is already finished.')]);
        }
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why the grant is stopped.')]);
        }
        if (WfApplication::query()->where('grant_id', $g->id)->where('status', 'requested')->exists()) {
            throw ValidationException::withMessages(['grant' => __('A discount from this grant is still waiting for Billing.')]);
        }
        $g->update(['status' => 'cancelled', 'decision_note' => mb_substr($reason, 0, 300)]);
        $this->audit->log('UPDATE', WfGrant::class, $g->id, null, ['status' => 'cancelled', 'reason' => $reason]);

        return $g;
    }

    /** What the grant can still give: its limit, less what was given and what is waiting for Billing. */
    public function remaining(WfGrant $g): string
    {
        $pending = (string) WfApplication::query()->where('grant_id', $g->id)->where('status', 'requested')->sum('amount');
        $left = bcsub(bcsub((string) $g->cap_amount, (string) $g->spent_amount, 2), number_format((float) $pending, 2, '.', ''), 2);

        return bccomp($left, '0', 2) < 0 ? '0.00' : $left;
    }

    /** Grants past their date lapse and release what they had set aside. @return int how many */
    public function expireDue(int $organizationId): int
    {
        return WfGrant::query()->where('organization_id', $organizationId)->where('status', 'approved')->whereDate('valid_to', '<', today())->update(['status' => 'expired']);
    }
}
