<?php

namespace App\Services\Portal;

use App\Models\Laboratory\LabCriticalResultAlert;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabReport;
use App\Models\Laboratory\LabResult;
use App\Models\Patient;
use App\Models\Portal\PortalNotification;
use App\Models\Portal\PortalReleasePolicy;
use App\Models\Portal\PortalResultRelease;
use App\Models\Radiology\RadiologyCriticalFinding;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyReport;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Whether a patient may see a lab or radiology report (guideline override 2). The report stays where it is; this keeps one decision row per report.
 *   immediate     — visible as soon as the report is final
 *   delayed       — visible N hours after it is final
 *   after_review  — visible when a clinician releases it in the queue
 *   never         — not shown online (the patient is told to ask the doctor)
 * A critical result is never released automatically, whatever the policy: it waits until the critical alert is acknowledged AND a clinician releases it.
 */
class ResultReleaseService
{
    private const STRICTNESS = ['immediate' => 0, 'delayed' => 1, 'after_review' => 2, 'never' => 3];

    public function __construct(private readonly PortalPolicy $policy, private readonly AuditLogger $audit, private readonly CommunicationService $comms) {}

    /**
     * Gives every recent final report that has no decision yet one. Safe to run again and again. Only reports from the last `release.sync_window_days` days are picked up
     * (an old backlog is brought in on purpose with `portal:check --since-days=N`, so a clinician is not buried in last year's reports on the first night).
     */
    public function sync(?int $organizationId = null, int $limit = 1000, ?int $sinceDays = null): int
    {
        $made = 0;
        $cut = now()->subDays($sinceDays ?? $this->policy->int('release.sync_window_days', 30));
        LabReport::query()->whereIn('status', ['final', 'amended'])->where(fn ($w) => $w->where('generated_at', '>=', $cut)->orWhereNull('generated_at'))->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->whereNotIn('id', PortalResultRelease::query()->where('source_type', 'lab_report')->select('source_id'))
            ->orderBy('id')->limit($limit)->get()->each(function (LabReport $report) use (&$made) {
                $patientId = LabOrder::query()->whereKey($report->lab_order_id)->value('patient_id');
                if ($patientId === null) {
                    return;
                }
                $this->open('lab_report', $report->id, $report->organization_id, (int) $patientId, __('Laboratory report :no', ['no' => $report->report_number]), $report->generated_at ?? $report->created_at);
                $made++;
            });

        RadiologyReport::query()->whereIn('status', ['final', 'amended'])->where('is_current', true)->where(fn ($w) => $w->where('approved_at', '>=', $cut)->orWhereNull('approved_at'))->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->whereNotIn('id', PortalResultRelease::query()->where('source_type', 'radiology_report')->select('source_id'))
            ->orderBy('id')->limit($limit)->get()->each(function (RadiologyReport $report) use (&$made) {
                $patientId = RadiologyExamination::query()->whereKey($report->examination_id)->value('patient_id');
                if ($patientId === null) {
                    return;
                }
                $this->open('radiology_report', $report->id, $report->organization_id, (int) $patientId, __('Radiology report :no', ['no' => $report->report_number]), $report->approved_at ?? $report->created_at);
                $made++;
            });

        return $made;
    }

    private function open(string $type, int $id, int $organizationId, int $patientId, string $title, $reportedAt): void
    {
        [$mode, $delay, $sensitive] = $this->decide($type, $id, $organizationId);
        $critical = $this->isCritical($type, $id);
        $status = match (true) {
            $critical => 'held_critical',
            $mode === 'never' => 'never',
            $mode === 'immediate' => 'released',
            default => 'pending',
        };
        try {
            $row = PortalResultRelease::create(['organization_id' => $organizationId, 'patient_id' => $patientId, 'source_type' => $type, 'source_id' => $id, 'title' => $title, 'status' => $status, 'mode' => $mode,
                'critical' => $critical, 'due_at' => $mode === 'delayed' ? ($reportedAt ?? now())->copy()->addHours($delay) : null, 'released_at' => $status === 'released' ? now() : null,
                'reason' => $sensitive ? 'sensitive test' : null, 'reported_at' => $reportedAt]);
        } catch (QueryException) {
            return; // another run made it first (unique per report)
        }
        if ($status === 'released') {
            $this->tell($row);
        }
    }

    /** @return array{0:string,1:int,2:bool} mode, delay hours, sensitive */
    public function decide(string $type, int $sourceId, int $organizationId): array
    {
        $rules = [];
        $names = [];
        if ($type === 'lab_report') {
            $report = LabReport::query()->find($sourceId);
            $tests = DB::table('lab_order_items as i')->leftJoin('lab_tests as t', 't.id', '=', 'i.test_id')->leftJoin('lab_test_categories as c', 'c.id', '=', 't.category_id')
                ->where('i.lab_order_id', $report?->lab_order_id)->get(['i.requested_test_name', 't.name as test_name', 'c.name as category_name', 'c.code as category_code']);
            foreach ($tests as $test) {
                $names[] = (string) ($test->test_name ?? $test->requested_test_name);
                if ($test->category_code !== null) {
                    $names[] = (string) $test->category_name;
                    $rules[] = PortalReleasePolicy::query()->where(['organization_id' => $organizationId, 'scope_type' => 'lab_category', 'scope_key' => $test->category_code])->first();
                }
            }
        } else {
            $rules[] = PortalReleasePolicy::query()->where(['organization_id' => $organizationId, 'scope_type' => 'radiology', 'scope_key' => 'all'])->first();
        }
        $default = PortalReleasePolicy::query()->where(['organization_id' => $organizationId, 'scope_type' => 'default', 'scope_key' => 'all'])->first();
        $rules = array_values(array_filter($rules)) ?: array_filter([$default]);

        $mode = (string) $this->policy->get('release.default_mode', 'after_review');
        $delay = $this->policy->int('release.default_delay_hours', 24);
        $sensitive = false;
        if ($rules !== []) {
            $mode = 'immediate';
            $delay = 0;
            foreach ($rules as $rule) {
                if (self::STRICTNESS[$rule->mode] >= self::STRICTNESS[$mode]) {
                    $mode = $rule->mode;
                }
                $delay = max($delay, (int) $rule->delay_hours);
                $sensitive = $sensitive || $rule->sensitive;
            }
        }
        $haystack = mb_strtolower(implode(' ', $names));
        foreach ((array) $this->policy->get('release.sensitive_words', []) as $word) {
            if ($word !== '' && str_contains($haystack, $word)) {
                $sensitive = true;
            }
        }
        if ($sensitive && self::STRICTNESS[$mode] < self::STRICTNESS['after_review']) {
            $mode = 'after_review';
        }

        return [$mode, $delay, $sensitive];
    }

    public function isCritical(string $type, int $sourceId): bool
    {
        if ($type === 'lab_report') {
            $report = LabReport::query()->find($sourceId);
            if ($report === null) {
                return false;
            }

            return LabResult::query()->whereIn('lab_order_item_id', DB::table('lab_order_items')->where('lab_order_id', $report->lab_order_id)->select('id'))->where('is_current', true)->where('critical_flag', true)->exists();
        }

        return RadiologyCriticalFinding::query()->where('report_id', $sourceId)->exists();
    }

    /** True once every critical alert behind the report has been acknowledged by a clinician. */
    public function criticalAcknowledged(PortalResultRelease $row): bool
    {
        if ($row->source_type === 'lab_report') {
            $report = LabReport::query()->find($row->source_id);
            $results = LabResult::query()->whereIn('lab_order_item_id', DB::table('lab_order_items')->where('lab_order_id', $report?->lab_order_id)->select('id'))->where('is_current', true)->where('critical_flag', true)->pluck('id');

            return $results->isEmpty() || LabCriticalResultAlert::query()->whereIn('result_id', $results)->whereNull('acknowledged_at')->doesntExist() && LabCriticalResultAlert::query()->whereIn('result_id', $results)->exists();
        }

        $findings = RadiologyCriticalFinding::query()->where('report_id', $row->source_id)->get();

        return $findings->isEmpty() || $findings->every(fn ($f) => $f->acknowledged_at !== null);
    }

    /** Timed releases. Never touches a critical result, a withheld one, or one waiting for review. */
    public function releaseDue(): int
    {
        $count = 0;
        PortalResultRelease::query()->where('status', 'pending')->where('mode', 'delayed')->where('critical', false)->where('due_at', '<=', now())->orderBy('id')->each(function (PortalResultRelease $row) use (&$count) {
            $row->update(['status' => 'released', 'released_at' => now()]);
            $this->tell($row);
            $count++;
        });

        return $count;
    }

    public function release(PortalResultRelease $row, User $by, ?string $reason = null): PortalResultRelease
    {
        return DB::transaction(function () use ($row, $by, $reason) {
            $row = PortalResultRelease::query()->lockForUpdate()->findOrFail($row->id);
            if ($row->status === 'released') {
                return $row;
            }
            if ($row->status === 'never' && ! $reason) {
                throw new PortalException(__('This result is set to be shown only in person. Give a reason to release it online.'));
            }
            if ($row->critical && ! $this->criticalAcknowledged($row)) {
                throw new PortalException(__('A critical result can be released only after the critical alert has been acknowledged by a clinician.'), 409);
            }
            $old = $row->status;
            $row->update(['status' => 'released', 'released_at' => now(), 'decided_by' => $by->id, 'reason' => $reason ?: $row->reason]);
            $this->audit->log('UPDATE', PortalResultRelease::class, $row->id, ['status' => $old], ['status' => 'released', 'by' => $by->id, 'reason' => $reason]);
            $this->tell($row);

            return $row;
        });
    }

    public function withhold(PortalResultRelease $row, User $by, string $reason): PortalResultRelease
    {
        $old = $row->status;
        $row->update(['status' => 'withheld', 'released_at' => null, 'decided_by' => $by->id, 'reason' => mb_substr($reason, 0, 200)]);
        $this->audit->log('UPDATE', PortalResultRelease::class, $row->id, ['status' => $old], ['status' => 'withheld', 'by' => $by->id, 'reason' => $reason]);

        return $row;
    }

    /** The queue for the clinician: waiting for review, held critical, or a due delayed item. */
    public function queue(int $organizationId)
    {
        return PortalResultRelease::query()->where('organization_id', $organizationId)->whereIn('status', ['pending', 'held_critical', 'withheld', 'never'])->orderByDesc('critical')->orderBy('reported_at');
    }

    private function tell(PortalResultRelease $row): void
    {
        PortalNotification::create(['organization_id' => $row->organization_id, 'patient_id' => $row->patient_id, 'kind' => 'result', 'title' => __('A new result is ready'), 'body' => (string) $row->title, 'link' => '/portal/results/'.$row->public_id]);
        $patient = Patient::query()->find($row->patient_id);
        if ($patient?->phone) { // the text never says what the result is
            $this->comms->send(['organization_id' => $row->organization_id, 'patient_id' => $row->patient_id, 'channel' => 'sms', 'purpose' => 'transactional', 'template' => 'result_ready', 'to' => PhoneNumber::normalize($patient->phone),
                'body' => __('A new report is ready in your HealthNexus portal.')]);
        }
    }
}
