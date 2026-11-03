<?php

namespace App\Interop\Mappers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Model;

class AppointmentMapper extends Mapper
{
    public function type(): string
    {
        return 'Appointment';
    }

    public function sources(): array
    {
        return [['model' => Appointment::class, 'entity' => 'Appointment', 'patient' => 'patient_id', 'search' => [
            'date' => fn ($q, $v) => $q->whereDate('appointment_date', $v),
            'status' => fn ($q, $v) => $q->whereIn('status', match ($v) {
                'booked' => ['scheduled', 'confirmed'], 'arrived' => ['checked_in'], 'fulfilled' => ['in_progress', 'completed'], 'cancelled' => ['cancelled'], 'noshow' => ['no_show'], default => ['__none__']
            }),
        ]]];
    }

    /** @param Appointment $m */
    public function toFhir(Model $m): array
    {
        $start = $m->appointment_date->copy()->setTimeFromTimeString($m->appointment_time->format('H:i'));
        $participants = [['actor' => $this->ref('Patient', Patient::class, $m->patient_id), 'status' => 'accepted']];
        if ($m->provider_id && ($doctor = $this->ref('Practitioner', Provider::class, $m->provider_id, null, 'Provider'))) {
            $participants[] = ['actor' => $doctor, 'status' => 'accepted'];
        }

        return array_filter($this->base($m) + [
            'identifier' => [['system' => 'urn:healthnexus:appointment', 'value' => $m->appointment_no]],
            'status' => match ($m->status) {
                'scheduled', 'confirmed' => 'booked', 'checked_in' => 'arrived', 'in_progress', 'completed' => 'fulfilled', 'cancelled' => 'cancelled', default => 'noshow'
            },
            'description' => $m->reason,
            'start' => $this->time($start),
            'participant' => $participants,
            'created' => $this->time($m->created_at),
        ], fn ($v) => $v !== null && $v !== []);
    }
}
