<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'encounter_type' => ['sometimes', 'string', 'max:100'],
            'encounter_type_id' => ['nullable', 'integer', 'exists:encounter_types,id'],
            'provider_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'priority' => ['sometimes', 'string', 'in:routine,urgent,stat'],
            'reason_for_visit' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:draft,registered,waiting,in_progress,paused,completed,cancelled,transferred,locked,amended'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
