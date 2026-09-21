<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\InvestigationOrder;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OpdService
{
    public function recordVitalSigns(Appointment $appointment, array $data, User $user): \App\Models\VitalSign
    {
        return DB::transaction(function () use ($appointment, $data, $user) {
            $data['company_id'] = $appointment->company_id;
            $data['branch_id'] = $appointment->branch_id;
            $data['appointment_id'] = $appointment->id;
            $data['patient_id'] = $appointment->patient_id;
            $data['recorded_by'] = $user->id;
            $data['recorded_at'] = now();

            return \App\Models\VitalSign::create($data);
        });
    }

    public function addDiagnosis(Appointment $appointment, array $data, User $user): Diagnosis
    {
        return DB::transaction(function () use ($appointment, $data, $user) {
            $data['company_id'] = $appointment->company_id;
            $data['branch_id'] = $appointment->branch_id;
            $data['appointment_id'] = $appointment->id;
            $data['patient_id'] = $appointment->patient_id;
            $data['doctor_id'] = $appointment->doctor_id;
            $data['recorded_by'] = $user->id;
            $data['recorded_at'] ??= now();

            return Diagnosis::create($data);
        });
    }

    public function addInvestigationOrder(Appointment $appointment, array $data, User $user): InvestigationOrder
    {
        $data['company_id'] = $appointment->company_id;
        $data['branch_id'] = $appointment->branch_id;
        $data['appointment_id'] = $appointment->id;
        $data['patient_id'] = $appointment->patient_id;
        $data['doctor_id'] = $appointment->doctor_id;
        $data['ordered_by'] = $user->id;
        $data['ordered_at'] ??= now();

        return InvestigationOrder::create($data);
    }

    public function createPrescription(Appointment $appointment, array $data, User $user): Prescription
    {
        return DB::transaction(function () use ($appointment, $data, $user) {
            $company = $appointment->company;
            $prefix = strtoupper(substr($company->code, 0, 3));
            $last = Prescription::where('company_id', $company->id)->orderByDesc('id')->first();
            $sequence = $last ? $last->id + 1 : 1;

            $prescription = Prescription::create([
                'company_id' => $appointment->company_id,
                'branch_id' => $appointment->branch_id,
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'prescription_no' => sprintf('%s-PRX-%08d', $prefix, $sequence),
                'clinical_notes' => $data['clinical_notes'] ?? null,
                'advice' => $data['advice'] ?? null,
                'created_by' => $user->id,
                'prescribed_at' => now(),
            ]);

            foreach ($data['items'] ?? [] as $itemData) {
                $prescription->items()->create(array_merge($itemData, ['company_id' => $company->id]));
            }

            return $prescription;
        });
    }

    public function updateAppointmentStatus(Appointment $appointment, string $status): Appointment
    {
        $appointment->update(['status' => $status]);
        return $appointment;
    }
}
