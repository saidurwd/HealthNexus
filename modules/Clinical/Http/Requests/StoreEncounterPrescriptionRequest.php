<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterPrescriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'clinical_notes' => ['nullable', 'string'],
            'advice' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_name' => ['required', 'string', 'max:255'],
            'items.*.dosage_form' => ['nullable', 'string', 'max:50'],
            'items.*.strength' => ['nullable', 'string', 'max:50'],
            'items.*.frequency' => ['required', 'string', 'max:100'],
            'items.*.duration' => ['required', 'string', 'max:50'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.instructions' => ['nullable', 'string'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
