<?php

namespace App\Services\Nursing\Fhir;

use App\Models\Nursing\NursingDevice;

class DeviceFhirMapper
{
    public function toDevice(NursingDevice $device): array
    {
        return [
            'resourceType' => 'Device',
            'id' => (string) $device->id,
            'status' => $device->status === NursingDevice::STATUS_ACTIVE ? 'active' : 'inactive',
            'type' => ['text' => $device->device_type],
            'patient' => ['reference' => "Patient/{$device->patient_id}"],
        ];
    }
}
