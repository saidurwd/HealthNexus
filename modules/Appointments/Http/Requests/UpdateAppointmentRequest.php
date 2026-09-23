<?php

namespace Modules\Appointments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * status is deliberately not accepted here — it previously let the generic update endpoint
     * set status to any of the seven values with no transition validation at all. Status now only
     * changes through the dedicated confirm/check-in/cancel/no-show endpoints, each enforced by
     * AppointmentStateMachine via AppointmentLifecycleService.
     */
    public function rules(): array
    {
        return [
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'slot_id' => ['nullable', 'integer', 'exists:appointment_slots,id'],
            'appointment_type_id' => ['nullable', 'integer', 'exists:appointment_types,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'room_id' => ['nullable', 'integer', 'exists:appointment_rooms,id'],
            'appointment_date' => ['nullable', 'date'],
            'appointment_time' => ['nullable', 'date_format:H:i'],
            'priority' => ['nullable', 'string', 'in:routine,urgent,stat'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
