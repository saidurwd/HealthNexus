<?php

namespace Tests\Feature\Ipd;

use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdPatientLeave;
use App\Models\Patient;
use App\Services\Ipd\IpdBedAllocationService;
use App\Services\Ipd\IpdLeaveService;

/**
 * Proves configurable bed handling during leave (spec §37): 'retain' keeps the allocation active
 * throughout; 'release' frees the bed immediately (never through Cleaning) and requires an
 * explicit bed on return.
 */
class LeaveWorkflowTest extends IpdTestCase
{
    public function test_retain_bed_handling_keeps_allocation_active_through_leave(): void
    {
        $bed = $this->makeBed();
        $admission = $this->makeAdmittedPatient($bed);

        $leave = app(IpdLeaveService::class)->request($admission, [
            'leave_type' => 'temporary_pass', 'expected_return_at' => now()->addHours(4), 'bed_handling' => 'retain',
        ], $this->user);

        app(IpdLeaveService::class)->approve($leave, $this->user);
        $leave = app(IpdLeaveService::class)->start($leave->fresh(), $this->user);

        $this->assertSame(IpdPatientLeave::STATUS_ON_LEAVE, $leave->status);
        $bed->refresh();
        $this->assertSame(IpdBed::STATUS_OCCUPIED, $bed->status, 'retain bed_handling must keep the bed occupied throughout leave.');
        $this->assertNotNull($admission->currentAllocation()->first());

        $leave = app(IpdLeaveService::class)->markReturned($leave, $this->user);
        $this->assertSame(IpdPatientLeave::STATUS_RETURNED, $leave->status);
        $this->assertNotNull($leave->actual_return_at);
    }

    public function test_release_bed_handling_frees_bed_without_cleaning_and_requires_a_bed_on_return(): void
    {
        $bed = $this->makeBed();
        $newBed = $this->makeBed();
        $admission = $this->makeAdmittedPatient($bed);

        $leave = app(IpdLeaveService::class)->request($admission, [
            'leave_type' => 'temporary_pass', 'expected_return_at' => now()->addHours(4), 'bed_handling' => 'release',
        ], $this->user);

        app(IpdLeaveService::class)->approve($leave, $this->user);
        app(IpdLeaveService::class)->start($leave->fresh(), $this->user);

        $bed->refresh();
        $this->assertSame(IpdBed::STATUS_AVAILABLE, $bed->status, 'release bed_handling must free the bed directly to Available, never Cleaning, since the patient is expected back.');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(IpdLeaveService::class)->markReturned($leave->fresh(), $this->user, null);
    }

    private function makeAdmittedPatient(IpdBed $bed): IpdAdmission
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
        ]);
        $admission = IpdAdmission::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $patient->id,
            'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);

        app(IpdBedAllocationService::class)->allocate($admission, $bed, $this->user);

        return $admission->fresh();
    }
}
