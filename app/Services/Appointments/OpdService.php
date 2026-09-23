<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\InvestigationOrder;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Clinical\ClinicalNumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Drives the actual OPD consultation workflow — the only clinical path a doctor uses end-to-end
 * today. Every write here now also stamps encounter_id when a $encounter is supplied (resolved by
 * OpdConsultationController via EncounterService::findOrCreateFromAppointment()), so this legacy,
 * appointment-shaped flow now correctly creates/drives a real Encounter underneath instead of
 * operating on the Appointment alone (spec's "Appointment ≠ Encounter" rule).
 */
class OpdService
{
    public function __construct(private ClinicalNumberGenerator $numberGenerator) {}

    public function recordVitalSigns(Appointment $appointment, array $data, User $user, ?Encounter $encounter = null): \App\Models\VitalSign
    {
        return DB::transaction(function () use ($appointment, $data, $user, $encounter) {
            $data['company_id'] = $appointment->company_id;
            $data['branch_id'] = $appointment->branch_id;
            $data['appointment_id'] = $appointment->id;
            $data['encounter_id'] = $encounter?->id;
            $data['patient_id'] = $appointment->patient_id;
            $data['recorded_by'] = $user->id;
            $data['recorded_at'] = now();

            return \App\Models\VitalSign::create($data);
        });
    }

    public function addDiagnosis(Appointment $appointment, array $data, User $user, ?Encounter $encounter = null): Diagnosis
    {
        return DB::transaction(function () use ($appointment, $data, $user, $encounter) {
            $data['company_id'] = $appointment->company_id;
            $data['branch_id'] = $appointment->branch_id;
            $data['appointment_id'] = $appointment->id;
            $data['encounter_id'] = $encounter?->id;
            $data['patient_id'] = $appointment->patient_id;
            $data['doctor_id'] = $appointment->doctor_id;
            $data['diagnosis_type'] ??= 'primary';
            $data['recorded_by'] = $user->id;
            $data['recorded_at'] ??= now();

            $diagnosis = Diagnosis::create($data);

            if ($encounter) {
                \App\Events\Clinical\DiagnosisAdded::dispatch($encounter, $diagnosis);
            }

            return $diagnosis;
        });
    }

    public function addInvestigationOrder(Appointment $appointment, array $data, User $user, ?Encounter $encounter = null): InvestigationOrder
    {
        $data['company_id'] = $appointment->company_id;
        $data['branch_id'] = $appointment->branch_id;
        $data['appointment_id'] = $appointment->id;
        $data['encounter_id'] = $encounter?->id;
        $data['patient_id'] = $appointment->patient_id;
        $data['doctor_id'] = $appointment->doctor_id;
        $data['ordered_by'] = $user->id;
        $data['ordered_at'] ??= now();

        return InvestigationOrder::create($data);
    }

    public function createPrescription(Appointment $appointment, array $data, User $user, ?Encounter $encounter = null): Prescription
    {
        return DB::transaction(function () use ($appointment, $data, $user, $encounter) {
            $prescription = Prescription::create([
                'company_id' => $appointment->company_id,
                'branch_id' => $appointment->branch_id,
                'appointment_id' => $appointment->id,
                'encounter_id' => $encounter?->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'prescription_no' => $this->numberGenerator->generatePrescriptionNumber($appointment->company, $appointment->branch_id),
                'status' => 'draft',
                'clinical_notes' => $data['clinical_notes'] ?? null,
                'advice' => $data['advice'] ?? null,
                'created_by' => $user->id,
                'prescribed_at' => now(),
            ]);

            foreach ($data['items'] ?? [] as $itemData) {
                $prescription->items()->create(array_merge($itemData, ['company_id' => $appointment->company_id]));
            }

            if ($encounter) {
                \App\Events\Clinical\PrescriptionCreated::dispatch($encounter, $prescription);
            }

            return $prescription;
        });
    }
}
