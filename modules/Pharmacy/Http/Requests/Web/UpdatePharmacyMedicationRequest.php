<?php

namespace Modules\Pharmacy\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePharmacyMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'generic_id' => ['nullable', 'integer', 'exists:pharmacy_generics,id'],
            'brand_id' => ['nullable', 'integer', 'exists:pharmacy_brands,id'],
            'dosage_form_id' => ['nullable', 'integer', 'exists:pharmacy_dosage_forms,id'],
            'route_id' => ['nullable', 'integer', 'exists:pharmacy_routes,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:50'],
            'strength_unit' => ['nullable', 'string', 'max:20'],
            'pack_size' => ['nullable', 'integer', 'min:1'],
            'dispensing_unit' => ['nullable', 'string', 'max:50'],
            'prescription_unit' => ['nullable', 'string', 'max:50'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'is_prescription_required' => ['boolean'],
            'is_controlled' => ['boolean'],
            'is_high_alert' => ['boolean'],
            'temperature_sensitive' => ['boolean'],
            'storage_temperature_min' => ['nullable', 'numeric'],
            'storage_temperature_max' => ['nullable', 'numeric'],
            'is_active' => ['boolean'],
        ];
    }
}
