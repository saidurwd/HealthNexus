<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterDiagnosisRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code_type' => ['nullable', 'string', 'max:50'],
            'coding_system' => ['nullable', 'string', 'max:50', 'in:ICD-10,ICD-11,SNOMED-CT,Other'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:500'],
            'status' => ['nullable', 'in:provisional,confirmed,rule_out,resolved'],
            'diagnosis_type' => ['nullable', 'in:primary,secondary,differential,historical'],
            'is_primary' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
