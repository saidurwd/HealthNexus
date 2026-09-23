<?php

namespace Tests\Feature\Radiology;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyProcedure;
use App\Models\User;
use App\Services\Radiology\RadiologySchedulingService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Proves the modality gap-lock conflict check (Phase 6 plan decision #1): two examinations
 * cannot be scheduled on the same modality at overlapping times.
 */
class SchedulingConflictTest extends RadiologyTestCase
{
    private RadiologyModality $modality;

    private RadiologyProcedure $procedure;

    protected function setUp(): void
    {
        parent::setUp();

        $this->procedure = RadiologyProcedure::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'XR-CHEST', 'name' => 'Chest X-Ray', 'modality_type' => 'XR', 'is_active' => true,
        ]);

        $this->modality = RadiologyModality::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'code' => 'XR-01', 'name' => 'X-Ray Room 1', 'modality_type' => 'XR', 'status' => 'online', 'is_active' => true,
        ]);
    }

    public function test_scheduling_two_examinations_on_the_same_modality_at_the_same_time_is_rejected(): void
    {
        $scheduling = app(RadiologySchedulingService::class);
        $dateTime = Carbon::parse('2026-10-01 10:00:00');

        $scheduling->schedule($this->makeOrderItem(), $this->modality, $this->makeTechnologist(), $dateTime, $this->user);

        $this->expectException(ValidationException::class);

        $scheduling->schedule($this->makeOrderItem(), $this->modality, $this->makeTechnologist(), $dateTime, $this->user);
    }

    public function test_scheduling_a_different_modality_at_the_same_time_succeeds(): void
    {
        $scheduling = app(RadiologySchedulingService::class);
        $dateTime = Carbon::parse('2026-10-01 10:00:00');

        $scheduling->schedule($this->makeOrderItem(), $this->modality, $this->makeTechnologist(), $dateTime, $this->user);

        $otherModality = RadiologyModality::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'code' => 'XR-02', 'name' => 'X-Ray Room 2', 'modality_type' => 'XR', 'status' => 'online', 'is_active' => true,
        ]);

        $examination = $scheduling->schedule($this->makeOrderItem(), $otherModality, $this->makeTechnologist(), $dateTime, $this->user);

        $this->assertSame('scheduled', $examination->status);
    }

    public function test_technologist_without_a_linked_user_account_cannot_be_scheduled(): void
    {
        $technologist = Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'user_id' => null,
            'provider_code' => 'TECH-NOLOGIN', 'name' => 'No Login Tech', 'provider_type' => 'radiology_technician', 'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        app(RadiologySchedulingService::class)->schedule($this->makeOrderItem(), $this->modality, $technologist, Carbon::parse('2026-10-01 10:00:00'), $this->user);
    }

    private function makeTechnologist(): Provider
    {
        $user = User::factory()->create();

        return Provider::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'user_id' => $user->id,
            'provider_code' => 'TECH-'.uniqid(), 'name' => $user->name, 'provider_type' => 'radiology_technician', 'status' => 'active',
        ]);
    }

    private function makeOrderItem()
    {
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

        return $order->items()->create([
            'procedure_id' => $this->procedure->id, 'requested_procedure_name' => 'Chest X-Ray',
            'status' => 'pending', 'result_status' => 'pending', 'requested_at' => now(),
        ]);
    }
}
