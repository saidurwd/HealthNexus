<?php

namespace App\Services\Icu\Fhir;

use App\Models\Icu\IcuVentilatorEvent;

/**
 * FHIR Device/DeviceUseStatement-shaped array from a ventilator event (spec §64: Ventilator ->
 * Device / DeviceUseStatement).
 */
class VentilatorDeviceFhirMapper
{
    public function toDeviceUseStatement(IcuVentilatorEvent $event): array
    {
        return [
            'resourceType' => 'DeviceUseStatement',
            'id' => (string) $event->id,
            'status' => in_array($event->event_type, [IcuVentilatorEvent::TYPE_EXTUBATED], true) ? 'completed' : 'active',
            'subject' => ['reference' => "Patient/{$event->episode?->patient_id}"],
            'device' => ['display' => 'Mechanical Ventilator'],
            'recordedOn' => $event->recorded_at?->toAtomString(),
            'note' => [[
                'text' => trim(sprintf(
                    'mode=%s fio2=%s peep=%s tidal_volume=%s rr=%s',
                    $event->mode ?? '-',
                    $event->fio2 ?? '-',
                    $event->peep ?? '-',
                    $event->tidal_volume ?? '-',
                    $event->respiratory_rate ?? '-',
                )),
            ]],
        ];
    }
}
