<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterReviewOfSystemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'system_name' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:normal,abnormal,not_assessed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
