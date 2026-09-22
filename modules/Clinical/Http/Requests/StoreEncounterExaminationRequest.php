<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterExaminationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'section_name' => ['required', 'string', 'max:100'],
            'findings' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
