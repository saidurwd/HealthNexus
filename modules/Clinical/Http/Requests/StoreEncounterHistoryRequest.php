<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterHistoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'history_type' => ['required', 'string', 'max:100'],
            'onset' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:100'],
            'course' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'string', 'max:50'],
            'associated_symptoms' => ['nullable', 'string', 'max:2000'],
            'aggravating_factors' => ['nullable', 'string', 'max:2000'],
            'relieving_factors' => ['nullable', 'string', 'max:2000'],
            'clinical_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
