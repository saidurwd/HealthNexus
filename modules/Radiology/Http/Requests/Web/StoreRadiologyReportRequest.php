<?php

namespace Modules\Radiology\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreRadiologyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_id' => ['nullable', 'integer', 'exists:radiology_report_templates,id'],
            'clinical_indication' => ['nullable', 'string'],
            'technique' => ['nullable', 'string'],
            'findings' => ['nullable', 'string'],
            'impression' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
        ];
    }
}
