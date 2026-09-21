<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVitalSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'temperature_unit' => ['in:celsius,fahrenheit'],
            'systolic' => ['nullable', 'integer', 'min:50', 'max:300'],
            'diastolic' => ['nullable', 'integer', 'min:30', 'max:200'],
            'bp_unit' => ['in:mmhg,atm,kpa'],
            'pulse_rate' => ['nullable', 'integer', 'min:30', 'max:250'],
            'respiratory_rate' => ['nullable', 'integer', 'min:8', 'max:50'],
            'height' => ['nullable', 'numeric', 'min:50', 'max:300'],
            'weight' => ['nullable', 'numeric', 'min:20', 'max:500'],
            'oxygen_saturation' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
