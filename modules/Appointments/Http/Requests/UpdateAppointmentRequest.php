<?php

namespace Modules\Appointments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'slot_id' => ['nullable', 'integer', 'exists:appointment_slots,id'],
            'appointment_date' => ['nullable', 'date'],
            'appointment_time' => ['nullable', 'date_format:H:i'],
            'status' => ['string', 'in:scheduled,confirmed,checked_in,in_progress,completed,cancelled,no_show'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        return parent::validated($key, $default);
    }
}
