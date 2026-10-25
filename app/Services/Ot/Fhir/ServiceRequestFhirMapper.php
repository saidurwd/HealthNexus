<?php

namespace App\Services\Ot\Fhir;

use App\Models\Ot\OtSurgeryRequest;

/**
 * FHIR ServiceRequest-shaped array from a surgery request — readiness only, no FHIR server.
 */
class ServiceRequestFhirMapper
{
    public function toServiceRequest(OtSurgeryRequest $request): array
    {
        return [
            'resourceType' => 'ServiceRequest',
            'id' => (string) $request->id,
            'status' => match ($request->status) {
                OtSurgeryRequest::STATUS_APPROVED => 'active',
                OtSurgeryRequest::STATUS_REJECTED, OtSurgeryRequest::STATUS_CANCELLED => 'revoked',
                default => 'draft',
            },
            'intent' => 'order',
            'priority' => $request->priority,
            'subject' => ['reference' => "Patient/{$request->patient_id}"],
            'encounter' => ['reference' => "Encounter/{$request->encounter_id}"],
            'code' => ['text' => $request->procedure?->name],
            'requester' => $request->requesting_provider_id ? ['reference' => "Practitioner/{$request->requesting_provider_id}"] : null,
            'reasonCode' => $request->indication ? [['text' => $request->indication]] : [],
            'bodySite' => $request->site ? [['text' => $request->site.($request->laterality ? " ({$request->laterality})" : '')]] : [],
        ];
    }
}
