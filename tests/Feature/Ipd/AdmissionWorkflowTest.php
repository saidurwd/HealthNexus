<?php

namespace Tests\Feature\Ipd;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\Encounter;
use App\Models\Ipd\IpdAdmissionRequest;
use App\Models\Ipd\IpdBed;
use App\Models\Patient;
use App\Services\Ipd\IpdAdmissionRequestService;
use App\Services\Ipd\IpdAdmissionService;

/**
 * Proves the full Admission Request -> Approval -> Admission -> Encounter -> Bed Allocation ->
 * Billing chain (spec §7-§10, §28, decisions #6/#11): approval with no configured workflow falls
 * back to a direct grant, admission reuses EncounterService::createEncounter() verbatim
 * (encounter_type='IPD'), the requested bed is occupied, and the one-time admission fee is
 * charged exactly once.
 */
class AdmissionWorkflowTest extends IpdTestCase
{
    public function test_request_approve_admit_creates_encounter_occupies_bed_and_charges_admission_fee(): void
    {
        $this->makeAdmissionFeeBillingItem();
        $bed = $this->makeBed();
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $request = app(IpdAdmissionRequestService::class)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'priority' => 'routine',
        ], $this->user);

        $this->assertSame(IpdAdmissionRequest::STATUS_PENDING_APPROVAL, $request->status, 'No ipd_admission workflow is seeded in this isolated test DB, so the request should land in pending_approval awaiting a direct approve() call.');

        $request = app(IpdAdmissionRequestService::class)->approve($request, $this->user);

        $this->assertSame(IpdAdmissionRequest::STATUS_APPROVED, $request->status);

        $admission = app(IpdAdmissionService::class)->admit([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'bed_id' => $bed->id,
        ], $this->user, $request);

        $this->assertNotEmpty($admission->admission_number);
        $this->assertSame('admitted', $admission->status);

        $this->assertNotNull($admission->encounter);
        $this->assertSame('IPD', $admission->encounter->encounter_type);
        $this->assertSame($patient->id, $admission->encounter->patient_id);

        $bed->refresh();
        $this->assertSame(IpdBed::STATUS_OCCUPIED, $bed->status);

        $currentAllocation = $admission->currentAllocation()->first();
        $this->assertNotNull($currentAllocation);
        $this->assertSame($bed->id, $currentAllocation->bed_id);

        $this->assertSame(1, BillingCharge::where('patient_id', $patient->id)->count());
        $charge = BillingCharge::where('patient_id', $patient->id)->first();
        $this->assertSame('ipd_admission_fee', $charge->billingItem->clinical_event_type);
    }

    public function test_admission_requires_an_approved_request(): void
    {
        $bed = $this->makeBed();
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $request = app(IpdAdmissionRequestService::class)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'priority' => 'routine',
        ], $this->user);

        $this->assertSame(IpdAdmissionRequest::STATUS_PENDING_APPROVAL, $request->status);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(IpdAdmissionService::class)->admit([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'bed_id' => $bed->id,
        ], $this->user, $request);
    }

    public function test_a_request_cannot_be_admitted_twice(): void
    {
        $this->makeAdmissionFeeBillingItem();
        $bed = $this->makeBed();
        $secondBed = $this->makeBed();
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);

        $request = app(IpdAdmissionRequestService::class)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'priority' => 'routine',
        ], $this->user);
        $request = app(IpdAdmissionRequestService::class)->approve($request, $this->user);

        app(IpdAdmissionService::class)->admit([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'bed_id' => $bed->id,
        ], $this->user, $request);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(IpdAdmissionService::class)->admit([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'patient_id' => $patient->id,
            'bed_id' => $secondBed->id,
        ], $this->user, $request->fresh());
    }

    private function makeAdmissionFeeBillingItem(): void
    {
        $category = BillingCategory::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Inpatient', 'code' => 'IPD', 'is_active' => true,
        ]);

        BillingItem::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'category_id' => $category->id, 'item_code' => 'IPD-ADM-general', 'item_type' => 'admission_fee',
            'name' => 'Admission Fee', 'base_price' => '500.00', 'is_taxable' => false,
            'is_clinically_chargeable' => true, 'clinical_event_type' => 'ipd_admission_fee',
            'clinical_event_key' => 'general', 'is_active' => true,
        ]);
    }
}
