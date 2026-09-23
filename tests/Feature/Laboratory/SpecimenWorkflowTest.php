<?php

namespace Tests\Feature\Laboratory;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;
use App\Services\Laboratory\SpecimenService;
use Illuminate\Validation\ValidationException;

class SpecimenWorkflowTest extends LabTestCase
{
    private LabOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        $specimenType = LabSpecimenType::create(['company_id' => $this->company->id, 'branch_id' => null, 'code' => 'BLOOD', 'name' => 'Blood', 'is_active' => true]);

        $test = LabTest::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'HGB', 'name' => 'Hemoglobin', 'test_type' => 'quantitative', 'unit' => 'g/dL',
            'specimen_type_id' => $specimenType->id, 'is_active' => true,
        ]);

        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $department = Department::create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'name' => 'OPD', 'code' => 'OPD-'.uniqid(), 'is_active' => true]);
        $encounter = Encounter::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
            'encounter_no' => 'ENC-'.uniqid(), 'encounter_type' => 'opd', 'provider_id' => $this->user->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $this->user->id,
        ]);

        $this->order = LabOrder::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'department_id' => $department->id,
            'patient_id' => $patient->id, 'encounter_id' => $encounter->id, 'order_number' => 'LAB-'.uniqid(),
            'priority' => 'routine', 'status' => 'registered', 'ordered_by' => $this->user->id, 'ordered_at' => now(),
        ]);

        $this->order->items()->create([
            'test_id' => $test->id, 'requested_test_name' => 'Hemoglobin', 'status' => 'pending',
            'result_status' => 'pending', 'requested_at' => now(),
        ]);
    }

    public function test_collecting_a_specimen_generates_a_unique_accession_number_and_advances_the_order(): void
    {
        $specimen = app(SpecimenService::class)->collect($this->order, [], $this->user);

        $this->assertNotEmpty($specimen->accession_number);
        $this->assertSame($specimen->accession_number, $specimen->barcode);
        $this->assertSame('collected', $specimen->status);
        $this->assertSame('collected', $this->order->fresh()->status);
        $this->assertSame('collected', $this->order->items()->first()->status);
    }

    public function test_two_specimens_never_share_an_accession_number(): void
    {
        $a = app(SpecimenService::class)->collect($this->order, [], $this->user);

        $this->order->update(['status' => 'awaiting_collection']);
        $b = app(SpecimenService::class)->collect($this->order, [], $this->user);

        $this->assertNotSame($a->accession_number, $b->accession_number);
    }

    public function test_receiving_a_specimen_advances_the_order_and_items(): void
    {
        $specimen = app(SpecimenService::class)->collect($this->order, [], $this->user);

        $specimen = app(SpecimenService::class)->receive($specimen, $this->user);

        $this->assertSame('received', $specimen->status);
        $this->assertSame('received', $this->order->fresh()->status);
        $this->assertSame('received', $this->order->items()->first()->status);
    }

    public function test_rejecting_a_specimen_requires_recollection_and_notifies(): void
    {
        $specimen = app(SpecimenService::class)->collect($this->order, [], $this->user);

        $specimen = app(SpecimenService::class)->reject($specimen, 'hemolysed', $this->user, 'Visibly hemolysed on receipt');

        $this->assertSame('rejected', $specimen->status);
        $this->assertSame('awaiting_collection', $this->order->fresh()->status);
        $this->assertSame('pending', $this->order->items()->first()->status);
        $this->assertNull($this->order->items()->first()->specimen_id);
    }

    public function test_rejection_reason_must_be_a_known_value(): void
    {
        $specimen = app(SpecimenService::class)->collect($this->order, [], $this->user);

        $this->expectException(ValidationException::class);

        app(SpecimenService::class)->reject($specimen, 'not_a_real_reason', $this->user);
    }
}
