<?php

namespace Modules\Radiology\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreRadiologyProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'section_id' => ['nullable', 'integer', 'exists:radiology_sections,id'],
            'body_part_id' => ['nullable', 'integer', 'exists:radiology_body_parts,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'modality_type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'turnaround_time_minutes' => ['nullable', 'integer', 'min:0'],
            'contrast_required' => ['boolean'],
            'preparation_required' => ['boolean'],
            'sedation_required' => ['boolean'],
            'preparation_instructions' => ['nullable', 'string'],
            'requires_senior_approval' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
