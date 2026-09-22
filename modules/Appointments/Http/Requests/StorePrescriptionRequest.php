<?php

namespace Modules\Appointments\Http\Requests;

use App\Services\TenantContextResolver;
use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
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

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $data['company_id'] = app(TenantContextResolver::class)->getCompanyId();
        $data['branch_id'] = app(TenantContextResolver::class)->getBranchId();

        return $data;
    }
}
