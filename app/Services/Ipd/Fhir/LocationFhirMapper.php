<?php

namespace App\Services\Ipd\Fhir;

use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdRoom;
use App\Models\Ipd\IpdWard;

/**
 * Produces FHIR-shaped Location arrays for the Hospital -> Ward -> Room -> Bed hierarchy
 * (spec §76) — pure mappers, no FHIR server. partOf chains a bed up through room and ward,
 * matching FHIR's Location.partOf hierarchy convention.
 */
class LocationFhirMapper
{
    public function wardToLocation(IpdWard $ward): array
    {
        return [
            'resourceType' => 'Location',
            'id' => "ward-{$ward->id}",
            'status' => $ward->is_active ? 'active' : 'inactive',
            'name' => $ward->name,
            'mode' => 'instance',
            'physicalType' => ['coding' => [['code' => 'wa', 'display' => 'Ward']]],
            'managingOrganization' => ['reference' => "Organization/{$ward->company_id}"],
        ];
    }

    public function roomToLocation(IpdRoom $room): array
    {
        return [
            'resourceType' => 'Location',
            'id' => "room-{$room->id}",
            'status' => $room->is_active ? 'active' : 'inactive',
            'name' => 'Room '.$room->room_number,
            'mode' => 'instance',
            'physicalType' => ['coding' => [['code' => 'ro', 'display' => 'Room']]],
            'partOf' => ['reference' => "Location/ward-{$room->ward_id}"],
        ];
    }

    public function bedToLocation(IpdBed $bed): array
    {
        return [
            'resourceType' => 'Location',
            'id' => "bed-{$bed->id}",
            'status' => $bed->status === IpdBed::STATUS_AVAILABLE ? 'active' : 'suspended',
            'name' => $bed->bed_name ?? $bed->bed_code,
            'mode' => 'instance',
            'physicalType' => ['coding' => [['code' => 'bd', 'display' => 'Bed']]],
            'partOf' => ['reference' => "Location/room-{$bed->room_id}"],
            'operationalStatus' => ['system' => 'http://terminology.hl7.org/CodeSystem/v2-0116', 'code' => $this->hl7BedStatusCode($bed->status)],
        ];
    }

    private function hl7BedStatusCode(string $status): string
    {
        return match ($status) {
            IpdBed::STATUS_OCCUPIED => 'O',
            IpdBed::STATUS_AVAILABLE => 'U',
            IpdBed::STATUS_CLEANING => 'H',
            IpdBed::STATUS_BLOCKED, IpdBed::STATUS_MAINTENANCE, IpdBed::STATUS_OUT_OF_SERVICE => 'K',
            IpdBed::STATUS_ISOLATION => 'I',
            default => 'U',
        };
    }
}
