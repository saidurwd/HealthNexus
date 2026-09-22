<?php

namespace Tests\Feature\Billing;

use App\Events\Clinical\EncounterCompleted;
use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;

class ChargeFromClinicalEventTest extends BillingTestCase
{
    public function test_encounter_completed_creates_a_charge_when_a_mapping_exists(): void
    {
        $category = BillingCategory::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Consultation', 'code' => 'CONSULT', 'is_active' => true,
        ]);

        BillingItem::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'category_id' => $category->id, 'item_code' => 'OPD-1', 'item_type' => 'consultation',
            'name' => 'OPD Consultation', 'base_price' => '500.00', 'is_taxable' => false,
            'is_clinically_chargeable' => true, 'clinical_event_type' => 'encounter',
            'clinical_event_key' => 'completed', 'is_active' => true,
        ]);

        $encounter = $this->makeEncounter();

        event(new EncounterCompleted($encounter));

        $this->assertDatabaseHas('billing_charges', [
            'encounter_id' => $encounter->id,
            'source_type' => Encounter::class,
            'source_id' => $encounter->id,
            'status' => 'pending',
        ]);

        $this->assertSame(1, BillingCharge::where('encounter_id', $encounter->id)->count());
    }

    public function test_encounter_completed_creates_no_charge_when_no_mapping_exists(): void
    {
        // No BillingItem with is_clinically_chargeable=true exists for this company.
        $encounter = $this->makeEncounter();

        event(new EncounterCompleted($encounter));

        $this->assertSame(0, BillingCharge::where('encounter_id', $encounter->id)->count());
    }

    public function test_redispatching_the_same_event_is_idempotent(): void
    {
        $category = BillingCategory::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Consultation', 'code' => 'CONSULT', 'is_active' => true,
        ]);

        BillingItem::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'category_id' => $category->id, 'item_code' => 'OPD-1', 'item_type' => 'consultation',
            'name' => 'OPD Consultation', 'base_price' => '500.00', 'is_taxable' => false,
            'is_clinically_chargeable' => true, 'clinical_event_type' => 'encounter',
            'clinical_event_key' => 'completed', 'is_active' => true,
        ]);

        $encounter = $this->makeEncounter();

        event(new EncounterCompleted($encounter));
        event(new EncounterCompleted($encounter));

        $this->assertSame(1, BillingCharge::where('encounter_id', $encounter->id)->count());
    }

    private function makeEncounter(): Encounter
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $department = Department::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'name' => 'General OPD', 'code' => 'GEN-OPD', 'is_active' => true,
        ]);

        return Encounter::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'patient_id' => $patient->id, 'encounter_no' => 'ENC-'.uniqid(),
            'encounter_type' => 'opd', 'provider_id' => $this->user->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'completed', 'source' => 'walk_in', 'created_by' => $this->user->id,
            'completed_by' => $this->user->id, 'completed_at' => now(),
        ]);
    }
}
