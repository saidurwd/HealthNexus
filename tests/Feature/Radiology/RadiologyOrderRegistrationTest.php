<?php

namespace Tests\Feature\Radiology;

use App\Events\Clinical\ClinicalOrderCreated;
use App\Models\Billing\BillingCharge;
use App\Models\ClinicalOrder;
use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyProcedure;

/**
 * Proves the Clinical Order -> Radiology Order -> per-procedure Billing Charge chain
 * (Phase 6 plan decision #8): a radiology ClinicalOrder becomes a registered RadiologyOrder
 * with an accession number and one RadiologyOrderItem per requested procedure, each matched
 * item produces exactly one BillingCharge keyed on the procedure's own code.
 */
class RadiologyOrderRegistrationTest extends RadiologyTestCase
{
    public function test_radiology_clinical_order_becomes_a_registered_order_with_accession_and_per_procedure_charges(): void
    {
        $procedure = $this->makeChargeableProcedure('CT-BRAIN', 'CT Brain');

        $clinicalOrder = $this->makeClinicalOrder('radiology', ['CT Brain']);

        event(new ClinicalOrderCreated($clinicalOrder->encounter, $clinicalOrder));

        $order = RadiologyOrder::where('clinical_order_id', $clinicalOrder->id)->first();

        $this->assertNotNull($order);
        $this->assertSame('registered', $order->status);
        $this->assertNotEmpty($order->accession_number);
        $this->assertSame(1, $order->items()->count());

        $item = $order->items()->first();
        $this->assertSame($procedure->id, $item->procedure_id);
        $this->assertSame('pending', $item->status);

        $this->assertSame(1, BillingCharge::query()->where('patient_id', $clinicalOrder->patient_id)->count());
        $charge = BillingCharge::query()->where('patient_id', $clinicalOrder->patient_id)->first();
        $this->assertSame('CT-BRAIN', $charge->billingItem->clinical_event_key);
    }

    public function test_unmatched_items_are_recorded_not_silently_dropped(): void
    {
        $clinicalOrder = $this->makeClinicalOrder('radiology', ['Some Unknown Scan']);

        event(new ClinicalOrderCreated($clinicalOrder->encounter, $clinicalOrder));

        $order = RadiologyOrder::where('clinical_order_id', $clinicalOrder->id)->first();
        $item = $order->items()->first();

        $this->assertTrue($item->isUnmatched());
        $this->assertSame('unmatched', $item->status);
        $this->assertSame('Some Unknown Scan', $item->requested_procedure_name);
        $this->assertSame(0, BillingCharge::count());
    }

    public function test_non_radiology_clinical_orders_do_not_create_a_radiology_order(): void
    {
        $clinicalOrder = $this->makeClinicalOrder('laboratory', ['CBC']);

        event(new ClinicalOrderCreated($clinicalOrder->encounter, $clinicalOrder));

        $this->assertSame(0, RadiologyOrder::where('clinical_order_id', $clinicalOrder->id)->count());
    }

    public function test_redispatching_the_same_clinical_order_event_does_not_double_charge(): void
    {
        $this->makeChargeableProcedure('CT-BRAIN', 'CT Brain');
        $clinicalOrder = $this->makeClinicalOrder('radiology', ['CT Brain']);

        event(new ClinicalOrderCreated($clinicalOrder->encounter, $clinicalOrder));

        $this->assertSame(1, BillingCharge::count());
    }

    private function makeChargeableProcedure(string $code, string $name): RadiologyProcedure
    {
        $procedure = RadiologyProcedure::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => $code, 'name' => $name, 'modality_type' => 'CT', 'is_active' => true,
        ]);

        $category = \App\Models\Billing\BillingCategory::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Diagnostics', 'code' => 'DIAG', 'is_active' => true,
        ]);

        \App\Models\Billing\BillingItem::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'category_id' => $category->id, 'item_code' => 'RAD-'.$code, 'item_type' => 'diagnostic',
            'name' => $name, 'base_price' => '4500.00', 'is_taxable' => false,
            'is_clinically_chargeable' => true, 'clinical_event_type' => 'radiology_procedure',
            'clinical_event_key' => $code, 'is_active' => true,
        ]);

        return $procedure;
    }

    private function makeClinicalOrder(string $orderType, array $itemNames): ClinicalOrder
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $department = Department::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'name' => 'General OPD', 'code' => 'GEN-OPD-'.uniqid(), 'is_active' => true,
        ]);

        $encounter = Encounter::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'patient_id' => $patient->id, 'encounter_no' => 'ENC-'.uniqid(),
            'encounter_type' => 'opd', 'provider_id' => $this->user->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $this->user->id,
        ]);

        $order = ClinicalOrder::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'order_number' => 'ORD-'.uniqid(), 'patient_id' => $patient->id, 'encounter_id' => $encounter->id,
            'provider_id' => $this->user->id, 'order_type' => $orderType, 'priority' => 'routine',
            'status' => 'requested', 'ordered_at' => now(), 'ordered_by' => $this->user->id,
        ]);

        foreach ($itemNames as $name) {
            $order->items()->create(['company_id' => $this->company->id, 'item_name' => $name, 'status' => 'pending']);
        }

        return $order->fresh(['items', 'encounter']);
    }
}
