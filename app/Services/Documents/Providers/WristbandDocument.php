<?php

namespace App\Services\Documents\Providers;

use App\Contracts\Documents\DocumentProvider;
use App\Models\Patient;
use App\Services\Documents\DocumentSource;

/** Reference = the patient's public id, so a numeric id is never put in a URL. */
class WristbandDocument implements DocumentProvider
{
    public function type(): string
    {
        return 'wristband';
    }

    public function permission(): string
    {
        return 'patients.print';
    }

    public function paper(): string
    {
        return 'label';
    }

    public function isFinancial(): bool
    {
        return false;
    }

    public function resolve(string $reference, int $organizationId): ?DocumentSource
    {
        $patient = Patient::query()->where('organization_id', $organizationId)->where('public_id', $reference)->first();

        if (! $patient) {
            return null;
        }

        return new DocumentSource(
            reference: $patient->public_id,
            variables: ['patient' => [
                'name' => $patient->full_name, 'number' => $patient->enterprise_patient_no,
                'date_of_birth' => $patient->date_of_birth?->format('d M Y'), 'sex' => $patient->sex,
            ]],
            patientId: $patient->id,
            barcodeText: $patient->enterprise_patient_no,
        );
    }
}
