<?php

namespace App\Services\Ot\Fhir;

use App\Models\PatientConsent;

class ConsentFhirMapper
{
    public function toConsent(PatientConsent $consent): array
    {
        return [
            'resourceType' => 'Consent',
            'id' => (string) $consent->id,
            'status' => $consent->status === 'active' ? 'active' : 'inactive',
            'scope' => ['text' => 'treatment'],
            'category' => [['text' => $consent->consent_type]],
            'patient' => ['reference' => "Patient/{$consent->patient_id}"],
            'dateTime' => $consent->granted_at?->toAtomString(),
            'performer' => $consent->granted_by ? [['reference' => "Practitioner/{$consent->granted_by}"]] : [],
            'sourceReference' => $consent->document_file_id ? ['reference' => "DocumentReference/{$consent->document_file_id}"] : null,
        ];
    }
}
