<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientProblemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'problem_code' => ['nullable', 'string', 'max:100'],
            'problem_name' => ['required', 'string', 'max:255'],
            'coding_system' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:active,resolved'],
            'onset_date' => ['nullable', 'date'],
            'resolved_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
