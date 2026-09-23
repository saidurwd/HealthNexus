<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLabTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:lab_test_categories,id'],
            'section_id' => ['nullable', 'integer', 'exists:lab_sections,id'],
            'specimen_type_id' => ['nullable', 'integer', 'exists:lab_specimen_types,id'],
            'container_type_id' => ['nullable', 'integer', 'exists:lab_container_types,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'test_type' => ['required', 'in:quantitative,qualitative,semi_quantitative,text,categorical,calculated,microbiology,culture,pathology'],
            'method' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'fasting_required' => ['boolean'],
            'turnaround_time_minutes' => ['nullable', 'integer', 'min:0'],
            'requires_pathologist_approval' => ['boolean'],
            'is_active' => ['boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
