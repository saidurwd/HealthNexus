<?php

namespace Tests\Feature\Laboratory;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Laboratory\LabContainerType;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabOrderItem;
use App\Models\Laboratory\LabReferenceRange;
use App\Models\Laboratory\LabSpecimen;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;
use App\Models\User;
use App\Services\Laboratory\ResultAmendmentService;
use App\Services\Laboratory\ResultEntryService;
use App\Services\Laboratory\ResultValidationService;
use Illuminate\Validation\ValidationException;

/**
 * Result Entry -> Technical Validation -> Pathologist Approval -> Amendment, covering the
 * auto reference-range flagging and immutable versioning rules from the Phase 5 spec.
 */
class ResultWorkflowTest extends LabTestCase
{
    private LabOrderItem $item;

    private LabTest $test;

    protected function setUp(): void
    {
        parent::setUp();

        $specimenType = LabSpecimenType::create(['company_id' => $this->company->id, 'branch_id' => null, 'code' => 'BLOOD', 'name' => 'Blood', 'is_active' => true]);
        $containerType = LabContainerType::create(['company_id' => $this->company->id, 'branch_id' => null, 'code' => 'EDTA', 'name' => 'EDTA Tube', 'is_active' => true]);

        $this->test = LabTest::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'HGB', 'name' => 'Hemoglobin', 'test_type' => 'quantitative', 'unit' => 'g/dL',
            'specimen_type_id' => $specimenType->id, 'container_type_id' => $containerType->id, 'is_active' => true,
        ]);

        LabReferenceRange::create([
            'test_id' => $this->test->id, 'gender' => 'any', 'unit' => 'g/dL',
            'low' => '12.0', 'high' => '16.0', 'pregnancy_status' => 'any', 'is_active' => true,
        ]);

        $patient = Patient::factory()->create(['company_id' => $this->company->id, 'sex' => 'M']);
        $department = Department::create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'name' => 'OPD', 'code' => 'OPD-'.uniqid(), 'is_active' => true]);
        $encounter = Encounter::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
            'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $this->user->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $this->user->id,
        ]);

        $order = LabOrder::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'department_id' => $department->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'LAB-'.uniqid(),
            'priority' => 'routine', 'status' => 'received', 'ordered_by' => $this->user->id, 'ordered_at' => now(),
        ]);

        $specimen = LabSpecimen::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'lab_order_id' => $order->id,
            'specimen_type_id' => $specimenType->id, 'accession_number' => 'ACC-'.uniqid(), 'status' => 'received',
            'collected_by' => $this->user->id, 'collected_at' => now(), 'received_by' => $this->user->id, 'received_at' => now(),
        ]);

        $this->item = LabOrderItem::create([
            'lab_order_id' => $order->id, 'test_id' => $this->test->id, 'specimen_id' => $specimen->id,
            'requested_test_name' => 'Hemoglobin', 'status' => 'received', 'result_status' => 'pending', 'requested_at' => now(),
        ]);
    }

    public function test_entering_a_normal_value_flags_it_normal_and_advances_the_order(): void
    {
        $result = app(ResultEntryService::class)->enter($this->item, ['numeric_value' => '14.0'], $this->user);

        $this->assertSame('normal', $result->abnormal_flag);
        $this->assertFalse($result->critical_flag);
        $this->assertSame('entered', $result->result_status);
        $this->assertSame('awaiting_validation', $this->item->labOrder->fresh()->status);
    }

    public function test_entering_a_low_value_flags_it_low(): void
    {
        $result = app(ResultEntryService::class)->enter($this->item, ['numeric_value' => '9.0'], $this->user);

        $this->assertSame('low', $result->abnormal_flag);
    }

    public function test_full_validation_workflow_moves_through_entered_technical_pathologist(): void
    {
        \App\Models\Laboratory\LabTest::whereKey($this->test->id)->update(['requires_pathologist_approval' => true]);

        $result = app(ResultEntryService::class)->enter($this->item, ['numeric_value' => '14.0'], $this->user);

        $technician = User::factory()->create();
        $result = app(ResultValidationService::class)->technicalValidate($result, $technician);
        $this->assertSame('technically_validated', $result->result_status);

        $pathologist = User::factory()->create();
        $result = app(ResultValidationService::class)->pathologistApprove($result, $pathologist);
        $this->assertSame('pathologist_validated', $result->result_status);

        $this->assertSame('validated', $this->item->fresh()->status);
        $this->assertSame('validated', $this->item->labOrder->fresh()->status);
    }

    public function test_technical_validation_cannot_be_done_by_the_user_who_entered_the_result(): void
    {
        $result = app(ResultEntryService::class)->enter($this->item, ['numeric_value' => '14.0'], $this->user);

        $this->expectException(ValidationException::class);

        app(ResultValidationService::class)->technicalValidate($result, $this->user);
    }

    public function test_test_not_requiring_pathologist_approval_is_auto_finalized_after_technical_validation(): void
    {
        $result = app(ResultEntryService::class)->enter($this->item, ['numeric_value' => '14.0'], $this->user);

        $technician = User::factory()->create();
        $result = app(ResultValidationService::class)->technicalValidate($result, $technician);

        $this->assertSame('validated', $this->item->fresh()->status);
        $this->assertSame('validated', $this->item->labOrder->fresh()->status);
    }

    public function test_critical_value_detection_sets_flag_and_creates_an_alert(): void
    {
        \App\Models\Laboratory\LabCriticalValue::create(['test_id' => $this->test->id, 'low_threshold' => '5.0', 'high_threshold' => '20.0', 'is_active' => true]);

        $result = app(ResultEntryService::class)->enter($this->item, ['numeric_value' => '3.0'], $this->user);

        $this->assertTrue($result->critical_flag);
        $this->assertSame('critical_low', $result->abnormal_flag);
        $this->assertDatabaseHas('lab_critical_result_alerts', ['result_id' => $result->id]);
    }

    public function test_amendment_never_overwrites_the_original_result(): void
    {
        $original = app(ResultEntryService::class)->enter($this->item, ['numeric_value' => '14.0'], $this->user);
        $originalId = $original->id;

        $amended = app(ResultAmendmentService::class)->amend($original, ['numeric_value' => '13.5'], 'Transcription error corrected', $this->user);

        $original = $original->fresh();
        $this->assertSame('14.0000', (string) $original->numeric_value);
        $this->assertFalse($original->is_current);

        $this->assertSame('13.5000', (string) $amended->numeric_value);
        $this->assertTrue($amended->is_current);
        $this->assertSame($originalId, $amended->amended_from_id);
        $this->assertSame(2, $amended->version);
    }

    public function test_only_the_current_version_can_be_amended(): void
    {
        $original = app(ResultEntryService::class)->enter($this->item, ['numeric_value' => '14.0'], $this->user);
        $amended = app(ResultAmendmentService::class)->amend($original, ['numeric_value' => '13.5'], 'Correction', $this->user);

        $this->expectException(ValidationException::class);

        app(ResultAmendmentService::class)->amend($original->fresh(), ['numeric_value' => '12.0'], 'Second correction attempt', $this->user);
    }
}
