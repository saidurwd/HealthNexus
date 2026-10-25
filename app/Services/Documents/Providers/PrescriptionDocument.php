<?php

namespace App\Services\Documents\Providers;

use App\Contracts\Documents\DocumentProvider;
use App\Models\Prescription;
use App\Models\Provider;
use App\Services\Documents\DocumentSource;

class PrescriptionDocument implements DocumentProvider
{
    public function type(): string
    {
        return 'prescription';
    }

    public function permission(): string
    {
        return 'encounter.print';
    }

    public function paper(): string
    {
        return 'a5';
    }

    public function isFinancial(): bool
    {
        return false;
    }

    public function resolve(string $reference, int $organizationId): ?DocumentSource
    {
        $prescription = Prescription::query()
            ->where('organization_id', $organizationId)->where('prescription_no', $reference)
            ->with(['patient', 'doctor', 'items', 'branch'])->first();

        if (! $prescription) {
            return null;
        }

        $license = Provider::query()->where('user_id', $prescription->doctor_id)->value('license_number');
        $no = 0;

        return new DocumentSource(
            reference: $prescription->prescription_no,
            variables: [
                'prescription_no' => $prescription->prescription_no,
                'prescribed_at' => $prescription->prescribed_at?->timezone(config('documents.timezone'))->format('d M Y'),
                'patient' => [
                    'name' => $prescription->patient?->full_name, 'number' => $prescription->patient?->enterprise_patient_no,
                    'date_of_birth' => $prescription->patient?->date_of_birth?->format('d M Y'),
                ],
                'doctor' => ['name' => $prescription->doctor?->name, 'registration_number' => $license],
                'clinical_notes' => $prescription->clinical_notes,
                'advice' => $prescription->advice,
                'items' => $prescription->items->map(fn ($i) => [
                    'no' => ++$no, 'medicine_name' => trim($i->medicine_name.' '.$i->dosage_form), 'strength' => $i->strength,
                    'frequency' => $i->frequency, 'duration' => $i->duration, 'instructions' => $i->instructions,
                ])->all(),
            ],
            patientId: $prescription->patient_id,
            branchId: $prescription->branch_id,
            qrPayload: 'HN:RX:'.$prescription->prescription_no,
        );
    }
}
