<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyOrder;
use App\Models\Prescription;
use Illuminate\Support\Facades\DB;

/**
 * Converts an issued Prescription into a PharmacyOrder + PharmacyOrderItems. Each PrescriptionItem's
 * free-text medicine_name is best-effort matched against the pharmacy_medications catalog — an
 * unmatched item is still recorded (medication_id left null, status 'unmatched') rather than
 * silently dropped, mirroring LabRegistrationService's ClinicalOrderItem matching.
 */
class PharmacyOrderService
{
    public function __construct(
        private readonly PharmacyNumberGenerator $numbers,
        private readonly PharmacyMedicationService $medications,
    ) {}

    public function registerFromPrescription(Prescription $prescription): PharmacyOrder
    {
        return DB::transaction(function () use ($prescription) {
            $order = PharmacyOrder::create([
                'company_id' => $prescription->company_id,
                'branch_id' => $prescription->branch_id,
                'prescription_id' => $prescription->id,
                'patient_id' => $prescription->patient_id,
                'encounter_id' => $prescription->encounter_id,
                'order_number' => $this->numbers->generateOrderNumber($prescription->company_id, $prescription->branch_id),
                'status' => 'pending',
                'ordered_by' => $prescription->issued_by,
                'ordered_at' => $prescription->issued_at ?? now(),
            ]);

            foreach ($prescription->items as $prescriptionItem) {
                $medication = $this->medications->findByFreeTextName(
                    $prescription->company_id,
                    $prescription->branch_id,
                    $prescriptionItem->medicine_name,
                );

                $order->items()->create([
                    'prescription_item_id' => $prescriptionItem->id,
                    'medication_id' => $medication?->id,
                    'requested_medicine_name' => $prescriptionItem->medicine_name,
                    'quantity_prescribed' => $prescriptionItem->quantity,
                    'quantity_dispensed' => 0,
                    'status' => $medication ? 'pending' : 'unmatched',
                ]);
            }

            return $order->load('items');
        });
    }
}
