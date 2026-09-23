<?php

namespace Tests\Feature\Laboratory;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabSpecimen;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;
use App\Services\Laboratory\LabReportService;
use App\Services\Laboratory\ResultAmendmentService;
use App\Services\Laboratory\ResultEntryService;
use App\Services\Laboratory\ResultValidationService;
use Illuminate\Validation\ValidationException;

class LabReportFinalizationTest extends LabTestCase
{
    private LabOrder $order;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $specimenType = LabSpecimenType::create(['company_id' => $this->company->id, 'branch_id' => null, 'code' => 'BLOOD', 'name' => 'Blood', 'is_active' => true]);
        $test = LabTest::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'HGB', 'name' => 'Hemoglobin', 'test_type' => 'quantitative', 'unit' => 'g/dL',
            'specimen_type_id' => $specimenType->id, 'is_active' => true,
        ]);

        $this->patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $department = Department::create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'name' => 'OPD', 'code' => 'OPD-'.uniqid(), 'is_active' => true]);
        $encounter = Encounter::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $this->patient->id,
            'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $this->user->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $this->user->id,
        ]);

        $this->order = LabOrder::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'department_id' => $department->id,
            'patient_id' => $this->patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'LAB-'.uniqid(),
            'priority' => 'routine', 'status' => 'received', 'ordered_by' => $this->user->id, 'ordered_at' => now(),
        ]);

        $specimen = LabSpecimen::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'lab_order_id' => $this->order->id,
            'specimen_type_id' => $specimenType->id, 'accession_number' => 'ACC-'.uniqid(), 'status' => 'received',
            'collected_by' => $this->user->id, 'collected_at' => now(), 'received_by' => $this->user->id, 'received_at' => now(),
        ]);

        $item = $this->order->items()->create([
            'test_id' => $test->id, 'specimen_id' => $specimen->id, 'requested_test_name' => 'Hemoglobin',
            'status' => 'received', 'result_status' => 'pending', 'requested_at' => now(),
        ]);

        $result = app(ResultEntryService::class)->enter($item, ['numeric_value' => '14.0'], $this->user);
        app(ResultValidationService::class)->technicalValidate($result, \App\Models\User::factory()->create());
    }

    public function test_finalizing_a_report_locks_results_and_moves_order_to_reported(): void
    {
        $report = app(LabReportService::class)->finalize($this->order->fresh(), $this->user);

        $this->assertSame('final', $report->status);
        $this->assertNotEmpty($report->report_number);
        $this->assertSame('reported', $this->order->fresh()->status);

        $result = $this->order->items()->first()->results()->where('is_current', true)->first();
        $this->assertSame('reported', $result->result_status);
        $this->assertNotNull($result->reported_at);
    }

    public function test_finalizing_a_report_records_a_patient_timeline_event(): void
    {
        $report = app(LabReportService::class)->finalize($this->order->fresh(), $this->user);

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $this->patient->id,
            'event_type' => 'LAB_REPORT_FINALIZED',
            'subject_id' => $report->id,
        ]);
    }

    public function test_cannot_finalize_before_the_order_is_fully_validated(): void
    {
        // The fixture's item is only technically_validated, not yet at order-level 'validated'
        // (single item with no pathologist requirement auto-advances it — force it back to
        // prove the guard works for a genuinely incomplete order).
        $this->order->update(['status' => 'awaiting_validation']);

        $this->expectException(ValidationException::class);

        app(LabReportService::class)->finalize($this->order->fresh(), $this->user);
    }

    public function test_amending_a_reported_result_marks_the_report_amended_and_records_timeline(): void
    {
        $report = app(LabReportService::class)->finalize($this->order->fresh(), $this->user);

        $reportedResult = $this->order->items()->first()->results()->where('is_current', true)->first();

        app(ResultAmendmentService::class)->amend($reportedResult, ['numeric_value' => '13.0'], 'Corrected transcription error', $this->user);

        $this->assertSame('amended', $report->fresh()->status);

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $this->patient->id,
            'event_type' => 'LAB_REPORT_AMENDED',
        ]);
    }
}
