<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterProcedureRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'procedure_code' => ['nullable', 'string', 'max:100'],
            'procedure_name' => ['required', 'string', 'max:255'],
            'procedure_date' => ['nullable', 'date'],
            'provider_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', 'in:planned,completed,cancelled'],
        ];
    }
}
