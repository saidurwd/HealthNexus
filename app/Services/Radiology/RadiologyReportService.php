<?php

namespace App\Services\Radiology;

use App\Events\Radiology\RadiologyReportAmended;
use App\Events\Radiology\RadiologyReportFinalized;
use App\Models\Provider;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Draft -> Submitted -> Under Review -> Approved -> Final (spec §38/§43). Amendment never
 * overwrites (spec §44/§45) — mirrors Laboratory's ResultAmendmentService versioning exactly.
 */
class RadiologyReportService
{
    public function __construct(private readonly RadiologyNumberGenerator $numbers) {}

    public function createDraft(RadiologyExamination $examination, Provider $radiologist, array $data = []): RadiologyReport
    {
        return DB::transaction(function () use ($examination, $radiologist, $data) {
            return RadiologyReport::create([
                'company_id' => $examination->company_id,
                'branch_id' => $examination->branch_id,
                'examination_id' => $examination->id,
                'template_id' => $data['template_id'] ?? null,
                'report_number' => $this->numbers->generate($examination->company_id, $examination->branch_id, 'RPT', 'RPT'),
                'status' => 'draft',
                'radiologist_id' => $radiologist->id,
                'clinical_indication' => $data['clinical_indication'] ?? $examination->orderItem->radiologyOrder->clinical_indication,
                'technique' => $data['technique'] ?? null,
                'findings' => $data['findings'] ?? null,
                'impression' => $data['impression'] ?? null,
                'recommendation' => $data['recommendation'] ?? null,
                'version' => 1,
                'is_current' => true,
            ]);
        });
    }

    public function update(RadiologyReport $report, array $data): RadiologyReport
    {
        if (! $report->isMutable()) {
            throw ValidationException::withMessages(['report' => "Cannot edit a report in '{$report->status}' status — use amend() instead."]);
        }

        $report->update($data);

        return $report->refresh();
    }

    public function submit(RadiologyReport $report): RadiologyReport
    {
        if ($report->status !== 'draft') {
            throw ValidationException::withMessages(['report' => "Cannot submit a report in '{$report->status}' status."]);
        }

        if (! $report->findings || ! $report->impression) {
            throw ValidationException::withMessages(['report' => 'Findings and impression are required before submitting.']);
        }

        $report->update(['status' => 'submitted', 'submitted_at' => now()]);

        return $report->refresh();
    }

    public function approve(RadiologyReport $report, Provider $approver): RadiologyReport
    {
        return DB::transaction(function () use ($report, $approver) {
            if (! in_array($report->status, ['submitted', 'under_review'], true)) {
                throw ValidationException::withMessages(['report' => "Cannot approve a report in '{$report->status}' status."]);
            }

            if ((int) $report->radiologist_id === $approver->id) {
                throw ValidationException::withMessages(['report' => 'A report cannot be approved by the same radiologist who authored it.']);
            }

            $report->update([
                'status' => 'final',
                'reviewed_by' => $approver->id,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            $examination = $report->examination;
            $order = $examination->orderItem->radiologyOrder;

            if (in_array($order->status, ['completed', 'images_available'], true)) {
                app(RadiologyOrderLifecycleService::class)->transitionTo($order, 'reporting');
            }

            app(RadiologyOrderLifecycleService::class)->transitionTo($order->fresh(), 'reported');
            $examination->orderItem->update(['status' => 'reported']);

            $report = $report->refresh();

            event(new RadiologyReportFinalized($report));

            return $report;
        });
    }

    /**
     * @param  array{clinical_indication?:string,technique?:string,findings?:string,impression?:string,recommendation?:string}  $data
     */
    public function amend(RadiologyReport $report, array $data, string $reason, User $user): RadiologyReport
    {
        return DB::transaction(function () use ($report, $data, $reason, $user) {
            if (! $report->is_current) {
                throw ValidationException::withMessages(['report' => 'Only the current version of a report can be amended.']);
            }

            if (! in_array($report->status, ['final', 'amended'], true)) {
                throw ValidationException::withMessages(['report' => "Cannot amend a report in '{$report->status}' status."]);
            }

            $amended = RadiologyReport::create([
                ...$report->only([
                    'company_id', 'branch_id', 'examination_id', 'template_id', 'radiologist_id',
                ]),
                'report_number' => $report->report_number,
                'status' => 'amended',
                'clinical_indication' => $data['clinical_indication'] ?? $report->clinical_indication,
                'technique' => $data['technique'] ?? $report->technique,
                'findings' => $data['findings'] ?? $report->findings,
                'impression' => $data['impression'] ?? $report->impression,
                'recommendation' => $data['recommendation'] ?? $report->recommendation,
                'reviewed_by' => $report->reviewed_by,
                'approved_by' => $report->approved_by,
                'approved_at' => $report->approved_at,
                'version' => $report->version + 1,
                'is_current' => true,
                'amended_from_id' => $report->id,
                'amendment_reason' => $reason,
                'amended_by' => $user->id,
            ]);

            $report->update(['is_current' => false]);

            event(new RadiologyReportAmended($report, $amended));

            return $amended;
        });
    }
}
