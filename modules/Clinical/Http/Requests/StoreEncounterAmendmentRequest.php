<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterAmendmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amendment_type' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:2000'],
            'content' => ['required', 'string', 'max:5000'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'approved_at' => ['nullable', 'date'],
        ];
    }
}
