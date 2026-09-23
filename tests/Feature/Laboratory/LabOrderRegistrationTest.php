<?php

namespace Tests\Feature\Laboratory;

use App\Events\Clinical\ClinicalOrderCreated;
use App\Models\Billing\BillingCharge;
use App\Models\ClinicalOrder;
use App\Models\Department;
use App\Models\Encounter;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabSpecimenType;
use App\Models\Laboratory\LabTest;
use App\Models\Patient;

/**
 * Proves the Clinical Order -> Lab Order -> per-test Billing Charge chain (Phase 5 plan
 * decision #2): a laboratory ClinicalOrder becomes a registered LabOrder with one LabOrderItem
 * per requested test, each matched item produces exactly one BillingCharge keyed on the test's
 * own code — never the generic 'clinical_order'/'laboratory' charge path.
 */
class LabOrderRegistrationTest extends LabTestCase
{
    public function test_laboratory_clinical_order_becomes_a_registered_lab_order_with_per_test_charges(): void
    {
        $test = $this->makeChargeableTest('HGB', 'Hemoglobin');

        $clinicalOrder = $this->makeClinicalOrder('laboratory', ['Hemoglobin']);

        event(new ClinicalOrderCreated($clinicalOrder->encounter, $clinicalOrder));

        $labOrder = LabOrder::where('clinical_order_id', $clinicalOrder->id)->first();

        $this->assertNotNull($labOrder);
        $this->assertSame('registered', $labOrder->status);
        $this->assertSame(1, $labOrder->items()->count());

        $item = $labOrder->items()->first();
        $this->assertSame($test->id, $item->test_id);
        $this->assertSame('pending', $item->status);

        $this->assertSame(1, BillingCharge::query()->where('patient_id', $clinicalOrder->patient_id)->count());
        $charge = BillingCharge::query()->where('patient_id', $clinicalOrder->patient_id)->first();
        $this->assertSame('HGB', $charge->billingItem->clinical_event_key);
    }

    public function test_unmatched_items_are_recorded_not_silently_dropped(): void
    {
        $clinicalOrder = $this->makeClinicalOrder('laboratory', ['Some Unknown Test']);

        event(new ClinicalOrderCreated($clinicalOrder->encounter, $clinicalOrder));

        $labOrder = LabOrder::where('clinical_order_id', $clinicalOrder->id)->first();
        $item = $labOrder->items()->first();

        $this->assertTrue($item->isUnmatched());
        $this->assertSame('unmatched', $item->status);
        $this->assertSame('Some Unknown Test', $item->requested_test_name);
        $this->assertSame(0, BillingCharge::count());
    }

    public function test_non_laboratory_clinical_orders_do_not_create_a_lab_order(): void
    {
        $clinicalOrder = $this->makeClinicalOrder('radiology', ['Chest X-Ray']);

        event(new ClinicalOrderCreated($clinicalOrder->encounter, $clinicalOrder));

        $this->assertSame(0, LabOrder::where('clinical_order_id', $clinicalOrder->id)->count());
    }

    public function test_redispatching_the_same_clinical_order_event_does_not_double_charge(): void
    {
        $this->makeChargeableTest('HGB', 'Hemoglobin');
        $clinicalOrder = $this->makeClinicalOrder('laboratory', ['Hemoglobin']);

        event(new ClinicalOrderCreated($clinicalOrder->encounter, $clinicalOrder));

        $this->assertSame(1, BillingCharge::count());
    }

    private function makeChargeableTest(string $code, string $name): LabTest
    {
        $specimenType = LabSpecimenType::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => 'BLOOD', 'name' => 'Blood', 'is_active' => true,
        ]);

        $test = LabTest::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'code' => $code, 'name' => $name, 'test_type' => 'quantitative',
            'unit' => 'g/dL', 'specimen_type_id' => $specimenType->id, 'is_active' => true,
        ]);

        $category = \App\Models\Billing\BillingCategory::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Diagnostics', 'code' => 'DIAG', 'is_active' => true,
        ]);

        \App\Models\Billing\BillingItem::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'category_id' => $category->id, 'item_code' => 'LAB-'.$code, 'item_type' => 'diagnostic',
            'name' => $name, 'base_price' => '300.00', 'is_taxable' => false,
            'is_clinically_chargeable' => true, 'clinical_event_type' => 'lab_test',
            'clinical_event_key' => $code, 'is_active' => true,
        ]);

        return $test;
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
