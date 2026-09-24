<?php

namespace Tests\Feature\Ipd;

use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedReservation;
use App\Models\Patient;
use App\Services\Ipd\IpdBedAllocationService;
use App\Services\Ipd\IpdBedBlockService;
use App\Services\Ipd\IpdBedReservationService;
use Illuminate\Validation\ValidationException;

/**
 * Negative tests proving unsafe allocations are rejected (spec §87): blocked/maintenance/
 * occupied beds cannot be allocated, an expired reservation cannot be used to bypass eligibility,
 * and a gender-restricted bed rejects a mismatched patient.
 */
class NegativeAllocationTest extends IpdTestCase
{
    public function test_blocked_bed_cannot_be_allocated(): void
    {
        $bed = $this->makeBed();
        app(IpdBedBlockService::class)->block($bed, 'maintenance', 'Plumbing repair', $this->user);

        $admission = $this->makeAdmission();

        $this->expectException(ValidationException::class);
        app(IpdBedAllocationService::class)->allocate($admission, $bed->fresh(), $this->user);
    }

    public function test_occupied_bed_cannot_be_allocated_to_a_second_admission(): void
    {
        $bed = $this->makeBed();
        $firstAdmission = $this->makeAdmission();
        app(IpdBedAllocationService::class)->allocate($firstAdmission, $bed, $this->user);

        $secondAdmission = $this->makeAdmission();

        $this->expectException(ValidationException::class);
        app(IpdBedAllocationService::class)->allocate($secondAdmission, $bed->fresh(), $this->user);
    }

    public function test_expired_reservation_does_not_block_a_new_allocation_attempt_by_another_patient(): void
    {
        $bed = $this->makeBed();
        $reservedPatient = Patient::factory()->create(['company_id' => $this->company->id]);

        $reservation = app(IpdBedReservationService::class)->reserve($bed, $reservedPatient, $this->user, null, null, 1);
        $reservation->update(['expires_at' => now()->subMinute()]);
        app(IpdBedReservationService::class)->expire($reservation->fresh());

        $bed->refresh();
        $this->assertSame(IpdBed::STATUS_AVAILABLE, $bed->status, 'An expired reservation must release the bed back to Available.');

        $otherAdmission = $this->makeAdmission();
        $allocation = app(IpdBedAllocationService::class)->allocate($otherAdmission, $bed->fresh(), $this->user);

        $this->assertNotNull($allocation);
    }

    public function test_reservation_for_a_different_patient_blocks_allocation(): void
    {
        $bed = $this->makeBed();
        $reservedPatient = Patient::factory()->create(['company_id' => $this->company->id]);
        app(IpdBedReservationService::class)->reserve($bed, $reservedPatient, $this->user);

        $otherAdmission = $this->makeAdmission();

        $this->expectException(ValidationException::class);
        app(IpdBedAllocationService::class)->allocate($otherAdmission, $bed->fresh(), $this->user);
    }

    public function test_gender_restricted_bed_rejects_mismatched_patient(): void
    {
        $bed = $this->makeBed(genderType: 'female');
        $malePatient = Patient::factory()->create(['company_id' => $this->company->id]);
        $gender = \App\Models\Gender::firstOrCreate(['code' => 'male'], ['name' => 'Male', 'sort_order' => 1]);
        $malePatient->update(['gender_id' => $gender->id]);

        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $malePatient->id,
        ]);
        $admission = IpdAdmission::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $malePatient->id,
            'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);

        $this->expectException(ValidationException::class);
        app(IpdBedAllocationService::class)->allocate($admission, $bed, $this->user);
    }

    private function makeAdmission(): IpdAdmission
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $encounter = Encounter::factory()->create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'patient_id' => $patient->id,
        ]);

        return IpdAdmission::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'admission_number' => 'ADM-TEST-'.uniqid(), 'patient_id' => $patient->id,
            'encounter_id' => $encounter->id, 'admitted_at' => now(), 'status' => 'admitted',
        ]);
    }
}
