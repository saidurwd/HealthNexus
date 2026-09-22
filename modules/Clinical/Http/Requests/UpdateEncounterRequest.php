<?php

namespace Modules\Clinical\Http\Requests;

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
            'encounter_type' => ['sometimes', 'string', 'in:OPD,IPD,ER,LAB,RADIOLOGY,FOLLOW_UP,TELEMEDICINE'],
            'attending_doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status' => ['sometimes', 'string', 'in:active,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
