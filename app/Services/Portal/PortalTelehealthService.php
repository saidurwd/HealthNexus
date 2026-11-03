<?php

namespace App\Services\Portal;

use App\Contracts\Integration\TelehealthGateway;
use App\Models\Appointment;

/** Video visits (S13): only the hand-off. The real consultation workflow, recording policy and e-prescription are Phase 24; with no provider installed the patient is told video is not on yet. */
class PortalTelehealthService
{
    public function __construct(private readonly TelehealthGateway $gateway) {}

    /** @return array{available:bool,url:?string,message:string} */
    public function joinInfo(Appointment $appointment): array
    {
        if (! $appointment->is_telemedicine) {
            return ['available' => false, 'url' => null, 'message' => __('This is a visit at the hospital.')];
        }
        $starts = $appointment->appointment_date->copy()->setTimeFromTimeString($appointment->appointment_time->format('H:i'));
        $room = $this->gateway->createRoom(['appointment_public_id' => $appointment->public_id, 'starts_at' => $starts->toIso8601String(), 'duration_minutes' => 30, 'participants' => []]);
        if (! $room->handled) {
            return ['available' => false, 'url' => null, 'message' => __('Video visits are not turned on yet. The hospital will call you.')];
        }

        return ['available' => true, 'url' => (string) ($room->data['url'] ?? ''), 'message' => __('Join a few minutes early.')];
    }
}
