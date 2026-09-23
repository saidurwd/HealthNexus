<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'encounter_type_id' => ['nullable', 'integer', 'exists:encounter_types,id'],
            'encounter_type' => ['nullable', 'string', 'max:100'],
            'provider_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'priority' => ['nullable', 'string', 'in:routine,urgent,stat'],
            'source' => ['nullable', 'string', 'max:100'],
            'reason_for_visit' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
