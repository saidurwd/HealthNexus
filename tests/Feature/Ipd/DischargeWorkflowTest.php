<?php

namespace Tests\Feature\Ipd;

use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdDischargeRequest;
use App\Models\Patient;
use App\Services\Ipd\IpdBedAllocationService;
use App\Services\Ipd\IpdDischargeService;
use Illuminate\Validation\ValidationException;

/**
 * Proves discharge planning + completion (spec §31-§35, plan decision #10): clearances are
 * order-agnostic and the request only becomes 'approved' once clinical/billing/pharmacy are all
 * recorded; complete() releases the bed to Cleaning (per default settings) and closes the
 * admission; duplicate completion is rejected.
 */
class DischargeWorkflowTest extends IpdTestCase
{
    public function test_discharge_requires_all_three_clearances_before_it_can_complete(): void
    {
        $bed = $this->makeBed();
        $admission = $this->makeAdmittedPatient($bed);

        $dischargeRequest = app(IpdDischargeService::class)->request($admission, ['discharge_type' => 'routine'], $this->user);
        $this->assertSame('discharge_planned', $admission->fresh()->status);

        $this->expectException(ValidationException::class);
        app(IpdDischargeService::class)->complete($dischargeRequest, $this->user);
    }

    public function test_discharge_completes_after_all_clearances_and_releases_bed(): void
    {
        $bed = $this->makeBed();
        $admission = $this->makeAdmittedPatient($bed);

        $dischargeRequest = app(IpdDischargeService::class)->request($admission, ['discharge_type' => 'routine'], $this->user);

        app(IpdDischargeService::class)->recordClearance($dischargeRequest, 'clinical', $this->user);
        app(IpdDischargeService::class)->recordClearance($dischargeRequest->fresh(), 'billing', $this->user);
        $dischargeRequest = app(IpdDischargeService::class)->recordClearance($dischargeRequest->fresh(), 'pharmacy', $this->user);

        $this->assertSame(IpdDischargeRequest::STATUS_APPROVED, $dischargeRequest->status);
        $this->assertSame('discharge_pending', $admission->fresh()->status);

        $dischargeRequest = app(IpdDischargeService::class)->complete($dischargeRequest, $this->user);

        $this->assertSame(IpdDischargeRequest::STATUS_COMPLETED, $dischargeRequest->status);

        $admission->refresh();
        $this->assertSame('discharged', $admission->status);
        $this->assertNotNull($admission->actual_discharge_date);
        $this->assertNull($admission->currentAllocation()->first());

        $bed->refresh();
        $this->assertSame(IpdBed::STATUS_CLEANING, $bed->status);
    }

    public function test_discharge_cannot_complete_twice(): void
    {
        $bed = $this->makeBed();
        $admission = $this->makeAdmittedPatient($bed);

        $dischargeRequest = app(IpdDischargeService::class)->request($admission, ['discharge_type' => 'routine'], $this->user);
        foreach (['clinical', 'billing', 'pharmacy'] as $type) {
            $dischargeRequest = app(IpdDischargeService::class)->recordClearance($dischargeRequest->fresh(), $type, $this->user);
        }

        app(IpdDischargeService::class)->complete($dischargeRequest, $this->user);

        $this->expectException(ValidationException::class);
        app(IpdDischargeService::class)->complete($dischargeRequest->fresh(), $this->user);
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
