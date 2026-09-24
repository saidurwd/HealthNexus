<?php

namespace Tests\Feature\Ipd;

use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedAllocation;
use App\Models\Ipd\IpdBedMovement;
use App\Models\Patient;
use App\Services\Ipd\IpdBedAllocationService;
use App\Services\Ipd\IpdTransferService;

/**
 * Proves a transfer is atomic (spec §15/§83): the patient is never simultaneously occupying two
 * active beds, the source bed is released (to Cleaning) and the destination is occupied, and the
 * movement ledger records exactly one completed transfer row.
 */
class TransferWorkflowTest extends IpdTestCase
{
    public function test_transfer_moves_patient_atomically_between_beds(): void
    {
        $sourceBed = $this->makeBed();
        $destinationBed = $this->makeBed();
        $admission = $this->makeAdmittedPatient($sourceBed);

        $movement = app(IpdTransferService::class)->request($admission, $destinationBed, $this->user);
        $this->assertSame(IpdBedMovement::STATUS_REQUESTED, $movement->status);
        $this->assertSame('transfer_requested', $admission->fresh()->status);

        app(IpdTransferService::class)->approve($movement, $this->user);
        $movement = app(IpdTransferService::class)->complete($movement->fresh(), $this->user);

        $this->assertSame(IpdBedMovement::STATUS_COMPLETED, $movement->status);
        $this->assertSame('active', $admission->fresh()->status);

        $sourceBed->refresh();
        $destinationBed->refresh();
        $this->assertSame(IpdBed::STATUS_CLEANING, $sourceBed->status);
        $this->assertSame(IpdBed::STATUS_OCCUPIED, $destinationBed->status);

        $this->assertSame(1, IpdBedAllocation::where('bed_id', $sourceBed->id)->whereNotNull('released_at')->count());
        $this->assertSame(1, IpdBedAllocation::where('bed_id', $destinationBed->id)->whereNull('released_at')->count());

        // Never simultaneously occupying two active beds.
        $this->assertSame(1, IpdBedAllocation::where('admission_id', $admission->id)->whereNull('released_at')->count());
    }

    public function test_transfer_cannot_complete_twice(): void
    {
        $sourceBed = $this->makeBed();
        $destinationBed = $this->makeBed();
        $admission = $this->makeAdmittedPatient($sourceBed);

        $movement = app(IpdTransferService::class)->request($admission, $destinationBed, $this->user);
        app(IpdTransferService::class)->complete($movement->fresh(), $this->user);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(IpdTransferService::class)->complete($movement->fresh(), $this->user);
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
