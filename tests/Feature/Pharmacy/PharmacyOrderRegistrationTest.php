<?php

namespace Tests\Feature\Pharmacy;

use App\Events\Clinical\PrescriptionIssued;
use App\Models\Patient;
use App\Models\Pharmacy\PharmacyOrder;
use App\Models\Pharmacy\PharmacyOrderItem;

/**
 * Proves the Prescription -> Pharmacy Order chain (Phase 7 plan decision #1):
 * an issued Prescription becomes a registered PharmacyOrder with one PharmacyOrderItem per
 * PrescriptionItem, best-effort matched against the pharmacy_medications catalog by name —
 * an unmatched item is preserved (flagged 'unmatched'), never silently dropped.
 */
class PharmacyOrderRegistrationTest extends PharmacyTestCase
{
    public function test_issuing_a_prescription_registers_a_pharmacy_order_with_matched_items(): void
    {
        $medication = $this->makeMedication('NAPA-500', 'Napa 500mg Tablet');
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $prescription = $this->makePrescription($patient, [
            ['medicine_name' => 'Napa 500mg Tablet', 'quantity' => 20],
        ]);

        event(new PrescriptionIssued($this->makeEncounterFor($prescription), $prescription));

        $order = PharmacyOrder::where('prescription_id', $prescription->id)->first();

        $this->assertNotNull($order);
        $this->assertSame('pending', $order->status);
        $this->assertNotEmpty($order->order_number);
        $this->assertSame(1, $order->items()->count());

        $item = $order->items()->first();
        $this->assertSame($medication->id, $item->medication_id);
        $this->assertSame(PharmacyOrderItem::STATUS_PENDING, $item->status);
        $this->assertSame(20, $item->quantity_prescribed);
    }

    public function test_unmatched_prescription_items_are_preserved_not_dropped(): void
    {
        $patient = Patient::factory()->create(['company_id' => $this->company->id]);
        $prescription = $this->makePrescription($patient, [
            ['medicine_name' => 'Some Unknown Drug', 'quantity' => 5],
        ]);

        event(new PrescriptionIssued($this->makeEncounterFor($prescription), $prescription));

        $order = PharmacyOrder::where('prescription_id', $prescription->id)->first();
        $item = $order->items()->first();

        $this->assertNull($item->medication_id);
        $this->assertSame(PharmacyOrderItem::STATUS_UNMATCHED, $item->status);
        $this->assertSame('Some Unknown Drug', $item->requested_medicine_name);
    }

    private function makeEncounterFor($prescription): \App\Models\Encounter
    {
        $department = \App\Models\Department::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'name' => 'OPD', 'code' => 'OPD-'.uniqid(), 'is_active' => true,
        ]);

        return \App\Models\Encounter::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'patient_id' => $prescription->patient_id, 'encounter_no' => 'ENC-'.uniqid(),
            'encounter_type' => 'opd', 'provider_id' => $this->user->id,
            'department_id' => $department->id, 'encounter_date' => now()->toDateString(),
            'status' => 'in_progress', 'source' => 'walk_in', 'created_by' => $this->user->id,
        ]);
    }
}
