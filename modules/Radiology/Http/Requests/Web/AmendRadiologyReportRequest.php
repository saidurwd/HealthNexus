<?php

namespace Modules\Radiology\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class AmendRadiologyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'findings' => ['nullable', 'string'],
            'impression' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }
}
