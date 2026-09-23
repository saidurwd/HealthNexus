<?php

namespace Tests\Feature\Radiology;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyProcedure;
use App\Models\User;
use App\Services\Radiology\RadiologyCriticalFindingService;
use App\Services\Radiology\RadiologyExaminationService;
use App\Services\Radiology\RadiologyReportService;
use App\Services\Radiology\RadiologySchedulingService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Check-in -> Preparation -> Start -> Complete, then Draft -> Submit -> Approve -> Final report,
 * amendment versioning, and critical-finding detection/acknowledgement — end to end.
 */
class ExaminationAndReportWorkflowTest extends RadiologyTestCase
{
    private RadiologyExamination $examination;

    private Provider $radiologist;

    protected function setUp(): void
    {
        parent::setUp();

        $procedure = RadiologyProcedure::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'CT-BRAIN', 'name' => 'CT Brain', 'modality_type' => 'CT', 'is_active' => true,
        ]);

        $modality = RadiologyModality::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'code' => 'CT-01', 'name' => 'CT Scanner 1', 'modality_type' => 'CT', 'status' => 'online', 'is_active' => true,
        ]);

        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $department = Department::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'name' => 'Radiology', 'code' => 'RAD-'.uniqid(), 'is_active' => true,
        ]);

        $encounter = Encounter::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
            'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $this->user->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $this->user->id,
        ]);

        $order = RadiologyOrder::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'department_id' => $department->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'RAD-'.uniqid(),
            'accession_number' => 'RAD-ACC-'.uniqid(), 'priority' => 'routine', 'status' => 'registered',
            'ordered_by' => $this->user->id, 'ordered_at' => now(),
        ]);

        $item = $order->items()->create([
            'procedure_id' => $procedure->id, 'requested_procedure_name' => 'CT Brain',
            'status' => 'pending', 'result_status' => 'pending', 'requested_at' => now(),
        ]);

        $technologistUser = User::factory()->create();
        $technologist = Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'user_id' => $technologistUser->id,
            'provider_code' => 'TECH-'.uniqid(), 'name' => $technologistUser->name, 'provider_type' => 'radiology_technician', 'status' => 'active',
        ]);

        $radiologistUser = User::factory()->create();
        $this->radiologist = Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'user_id' => $radiologistUser->id,
            'provider_code' => 'RAD-'.uniqid(), 'name' => $radiologistUser->name, 'provider_type' => 'radiologist', 'status' => 'active',
        ]);

        $this->examination = app(RadiologySchedulingService::class)->schedule($item, $modality, $technologist, Carbon::parse('2026-10-01 09:00:00'), $this->user);
    }

    public function test_examination_moves_through_the_full_workflow_with_tat_timestamps(): void
    {
        $service = app(RadiologyExaminationService::class);

        $exam = $service->checkIn($this->examination, $this->user);
        $this->assertSame('checked_in', $exam->status);
        $this->assertNotNull($exam->check_in_at);

        $exam = $service->start($exam, $this->user);
        $this->assertSame('in_progress', $exam->status);
        $this->assertNotNull($exam->started_at);

        $exam = $service->complete($exam, $this->user);
        $this->assertSame('completed', $exam->status);
        $this->assertNotNull($exam->completed_at);
        $this->assertSame('completed', $exam->orderItem->radiologyOrder->fresh()->status);
    }

    public function test_full_report_workflow_from_draft_to_final(): void
    {
        $exam = $this->completeExamination();

        $report = app(RadiologyReportService::class)->createDraft($exam, $this->radiologist, [
            'findings' => 'No acute intracranial abnormality.',
            'impression' => 'Normal CT brain.',
        ]);

        $this->assertSame('draft', $report->status);

        $report = app(RadiologyReportService::class)->submit($report);
        $this->assertSame('submitted', $report->status);

        $approverUser = User::factory()->create();
        $approver = Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'user_id' => $approverUser->id,
            'provider_code' => 'RAD-2-'.uniqid(), 'name' => $approverUser->name, 'provider_type' => 'senior_radiologist', 'status' => 'active',
        ]);

        $report = app(RadiologyReportService::class)->approve($report, $approver);
        $this->assertSame('final', $report->status);
        $this->assertSame('reported', $exam->orderItem->fresh()->status);
        $this->assertSame('reported', $exam->orderItem->radiologyOrder->fresh()->status);
    }

    public function test_report_cannot_be_approved_by_its_own_author(): void
    {
        $exam = $this->completeExamination();

        $report = app(RadiologyReportService::class)->createDraft($exam, $this->radiologist, ['findings' => 'x', 'impression' => 'y']);
        $report = app(RadiologyReportService::class)->submit($report);

        $this->expectException(ValidationException::class);

        app(RadiologyReportService::class)->approve($report, $this->radiologist);
    }

    public function test_amendment_never_overwrites_the_final_report(): void
    {
        $exam = $this->completeExamination();

        $report = app(RadiologyReportService::class)->createDraft($exam, $this->radiologist, ['findings' => 'Original finding', 'impression' => 'Original impression']);
        $report = app(RadiologyReportService::class)->submit($report);

        $approverUser = User::factory()->create();
        $approver = Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'user_id' => $approverUser->id,
            'provider_code' => 'RAD-2-'.uniqid(), 'name' => $approverUser->name, 'provider_type' => 'senior_radiologist', 'status' => 'active',
        ]);
        $final = app(RadiologyReportService::class)->approve($report, $approver);
        $finalId = $final->id;

        $amended = app(RadiologyReportService::class)->amend($final, ['impression' => 'Corrected impression'], 'Transcription error corrected', $this->user);

        $final = $final->fresh();
        $this->assertSame('Original impression', $final->impression);
        $this->assertFalse($final->is_current);

        $this->assertSame('Corrected impression', $amended->impression);
        $this->assertTrue($amended->is_current);
        $this->assertSame($finalId, $amended->amended_from_id);
        $this->assertSame(2, $amended->version);
    }

    public function test_critical_finding_is_detected_notified_and_acknowledged(): void
    {
        $exam = $this->completeExamination();

        $report = app(RadiologyReportService::class)->createDraft($exam, $this->radiologist, ['findings' => 'Large hemorrhage.', 'impression' => 'Acute intracranial hemorrhage.']);

        $finding = app(RadiologyCriticalFindingService::class)->flag($report, 'Acute intracranial hemorrhage requiring immediate attention', $this->radiologist);

        $this->assertSame('notified', $finding->fresh()->status);
        $this->assertNotNull($finding->fresh()->notified_at);

        $acknowledged = app(RadiologyCriticalFindingService::class)->acknowledge($finding, $this->user);
        $this->assertTrue($acknowledged->isAcknowledged());
        $this->assertSame('acknowledged', $acknowledged->status);
    }

    private function completeExamination(): RadiologyExamination
    {
        $service = app(RadiologyExaminationService::class);
        $exam = $service->checkIn($this->examination, $this->user);
        $exam = $service->start($exam, $this->user);

        return $service->complete($exam, $this->user);
    }
}
