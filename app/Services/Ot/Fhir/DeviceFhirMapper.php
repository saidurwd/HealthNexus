<?php

namespace App\Services\Ot\Fhir;

use App\Models\Ot\OtImplant;

class DeviceFhirMapper
{
    public function toDevice(OtImplant $implant): array
    {
        return [
            'resourceType' => 'Device',
            'id' => (string) $implant->id,
            'status' => 'active',
            'type' => ['text' => $implant->implant_name],
            'manufacturer' => $implant->manufacturer,
            'lotNumber' => $implant->lot_batch,
            'serialNumber' => $implant->serial_number,
            'expirationDate' => $implant->expiry_date?->toDateString(),
            'patient' => ['reference' => "Patient/{$implant->surgery?->patient_id}"],
        ];
    }
}
