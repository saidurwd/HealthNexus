<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterComplaintRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'complaint' => ['required', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:100'],
            'duration_unit' => ['nullable', 'string', 'max:50'],
            'onset' => ['nullable', 'string', 'max:100'],
            'severity' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
