<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\Pharmacy\PharmacySafetyAlert;
use App\Services\Pharmacy\PharmacyDispensingService;
use App\Services\Pharmacy\PharmacyOrderService;
use Illuminate\Validation\ValidationException;

/**
 * Proves the alert-not-block medication safety design (Phase 7 plan decision #5): a case-
 * insensitive allergy substring match raises a pharmacy_safety_alerts row and blocks dispensing
 * until a pharmacist explicitly overrides it (reasoned + audited) — never a silent block, and
 * never silently bypassed.
 */
class SafetyAlertTest extends PharmacyTestCase
{
    public function test_allergy_match_blocks_dispensing_until_overridden(): void
    {
        $medication = $this->makeMedication('NAPA-500', 'Napa 500mg Tablet');
        $store = $this->makeStore();
        $this->receiveBatch($store, $medication, 30);

        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        PatientAllergy::create([
            'company_id' => $this->company->id, 'patient_id' => $patient->id,
            'substance' => 'Paracetamol', 'severity' => 'severe', 'is_active' => true,
        ]);

        $prescription = $this->makePrescription($patient, [
            ['medicine_name' => 'Napa 500mg Tablet', 'quantity' => 10],
        ]);
        $order = app(PharmacyOrderService::class)->registerFromPrescription($prescription);
        $orderItem = $order->items->first();

        try {
            app(PharmacyDispensingService::class)->dispense($order, $store, [
                ['order_item_id' => $orderItem->id, 'quantity' => 10],
            ], $this->user);
            $this->fail('Dispensing must be blocked by an unresolved allergy safety alert.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('safety_alert', $e->errors());
        }

        $alert = PharmacySafetyAlert::where('order_item_id', $orderItem->id)->first();
        $this->assertNotNull($alert);
        $this->assertSame(PharmacySafetyAlert::TYPE_ALLERGY, $alert->alert_type);
        $this->assertFalse($alert->is_overridden);

        app(\App\Services\Pharmacy\PharmacyDispensingSafetyService::class)->override($alert, 'Clinically justified, prescriber consulted.', $this->user);

        $dispensing = app(PharmacyDispensingService::class)->dispense($order, $store, [
            ['order_item_id' => $orderItem->id, 'quantity' => 10],
        ], $this->user);

        $this->assertSame('fully_dispensed', $dispensing->status);

        $alert->refresh();
        $this->assertTrue($alert->is_overridden);
        $this->assertSame($this->user->id, $alert->overridden_by);
        $this->assertNotEmpty($alert->override_reason);
    }

    public function test_no_allergy_match_means_no_alert_raised(): void
    {
        $medication = $this->makeMedication('NAPA-500', 'Napa 500mg Tablet');
        $store = $this->makeStore();
        $this->receiveBatch($store, $medication, 30);

        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        PatientAllergy::create([
            'company_id' => $this->company->id, 'patient_id' => $patient->id,
            'substance' => 'Penicillin', 'severity' => 'severe', 'is_active' => true,
        ]);

        $prescription = $this->makePrescription($patient, [
            ['medicine_name' => 'Napa 500mg Tablet', 'quantity' => 10],
        ]);
        $order = app(PharmacyOrderService::class)->registerFromPrescription($prescription);
        $orderItem = $order->items->first();

        $dispensing = app(PharmacyDispensingService::class)->dispense($order, $store, [
            ['order_item_id' => $orderItem->id, 'quantity' => 10],
        ], $this->user);

        $this->assertSame('fully_dispensed', $dispensing->status);
        $this->assertSame(0, PharmacySafetyAlert::where('order_item_id', $orderItem->id)->count());
    }
}
