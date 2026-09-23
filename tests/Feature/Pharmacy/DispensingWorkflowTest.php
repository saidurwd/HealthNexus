<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Billing\BillingCategory;
use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\Patient;
use App\Models\Pharmacy\PharmacyDispensing;
use App\Models\Pharmacy\PharmacyOrder;
use App\Models\Pharmacy\PharmacyStock;
use App\Services\Pharmacy\PharmacyDispensingService;
use App\Services\Pharmacy\PharmacyOrderService;
use Illuminate\Validation\ValidationException;

/**
 * Proves the dispensing transactional core (spec §88, Phase 7 plan decision #4): stock is
 * deducted via FEFO, a billing charge is created for the actual dispensed quantity (not
 * prescription-issue time, not hardcoded to 1), partial dispensing leaves the order item with the
 * correct remaining quantity, and the order is never marked fully_dispensed early.
 */
class DispensingWorkflowTest extends PharmacyTestCase
{
    public function test_full_dispensing_deducts_stock_charges_billing_and_completes_the_order(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();
        $this->receiveBatch($store, $medication, 30);
        $this->makeChargeableBillingItem($medication->code);

        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $prescription = $this->makePrescription($patient, [
            ['medicine_name' => $medication->name, 'quantity' => 10],
        ]);

        $order = app(PharmacyOrderService::class)->registerFromPrescription($prescription);
        $orderItem = $order->items->first();

        $dispensing = app(PharmacyDispensingService::class)->dispense($order, $store, [
            ['order_item_id' => $orderItem->id, 'quantity' => 10],
        ], $this->user);

        $this->assertSame(PharmacyDispensing::STATUS_FULLY_DISPENSED, $dispensing->status);
        $this->assertSame(1, $dispensing->items()->count());
        $this->assertSame(10, $dispensing->items()->first()->quantity_dispensed);

        $stock = PharmacyStock::where('store_id', $store->id)->where('medication_id', $medication->id)->first();
        $this->assertSame(20, $stock->quantity_available);

        $orderItem->refresh();
        $this->assertSame(10, $orderItem->quantity_dispensed);
        $this->assertSame('dispensed', $orderItem->status);

        $order->refresh();
        $this->assertSame('fully_dispensed', $order->status);

        $this->assertSame(1, BillingCharge::where('patient_id', $patient->id)->count());
        $charge = BillingCharge::where('patient_id', $patient->id)->first();
        $this->assertSame(10, $charge->quantity);
        $this->assertSame($medication->code, $charge->billingItem->clinical_event_key);
    }

    public function test_partial_dispensing_leaves_correct_remaining_quantity_and_never_marks_order_fully_dispensed_early(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();
        $this->receiveBatch($store, $medication, 30);

        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $prescription = $this->makePrescription($patient, [
            ['medicine_name' => $medication->name, 'quantity' => 10],
        ]);

        $order = app(PharmacyOrderService::class)->registerFromPrescription($prescription);
        $orderItem = $order->items->first();

        app(PharmacyDispensingService::class)->dispense($order, $store, [
            ['order_item_id' => $orderItem->id, 'quantity' => 4],
        ], $this->user);

        $orderItem->refresh();
        $this->assertSame(4, $orderItem->quantity_dispensed);
        $this->assertSame(6, $orderItem->remainingQuantity());
        $this->assertSame('partially_dispensed', $orderItem->status);

        $order->refresh();
        $this->assertSame('partially_dispensed', $order->status);
    }

    public function test_dispensing_more_than_remaining_quantity_is_rejected(): void
    {
        $medication = $this->makeMedication();
        $store = $this->makeStore();
        $this->receiveBatch($store, $medication, 30);

        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $prescription = $this->makePrescription($patient, [
            ['medicine_name' => $medication->name, 'quantity' => 5],
        ]);

        $order = app(PharmacyOrderService::class)->registerFromPrescription($prescription);
        $orderItem = $order->items->first();

        $this->expectException(ValidationException::class);

        app(PharmacyDispensingService::class)->dispense($order, $store, [
            ['order_item_id' => $orderItem->id, 'quantity' => 10],
        ], $this->user);
    }

    public function test_dispensing_an_unmatched_item_without_explicit_medication_is_rejected(): void
    {
        $store = $this->makeStore();
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $prescription = $this->makePrescription($patient, [
            ['medicine_name' => 'Totally Unknown Drug', 'quantity' => 5],
        ]);

        $order = app(PharmacyOrderService::class)->registerFromPrescription($prescription);
        $orderItem = $order->items->first();

        $this->expectException(ValidationException::class);

        app(PharmacyDispensingService::class)->dispense($order, $store, [
            ['order_item_id' => $orderItem->id, 'quantity' => 5],
        ], $this->user);
    }

    private function makeChargeableBillingItem(string $medicationCode): void
    {
        $category = BillingCategory::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'name' => 'Pharmacy', 'code' => 'PHARM', 'is_active' => true,
        ]);

        BillingItem::create([
            'company_id' => $this->company->id, 'branch_id' => null,
            'category_id' => $category->id, 'item_code' => 'PH-'.$medicationCode, 'item_type' => 'medication',
            'name' => $medicationCode, 'base_price' => '5.00', 'is_taxable' => false,
            'is_clinically_chargeable' => true, 'clinical_event_type' => 'pharmacy_medication',
            'clinical_event_key' => $medicationCode, 'is_active' => true,
        ]);
    }
}
