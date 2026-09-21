<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvestigationOrderRequest extends FormRequest
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
            'test_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'clinical_notes' => ['nullable', 'string'],
            'priority' => ['in:routine,urgent,stat'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $data['company_id'] = app(\App\Services\TenantContextResolver::class)->getCompanyId();
        $data['branch_id'] = app(\App\Services\TenantContextResolver::class)->getBranchId();
        $data['order_no'] = 'LAB-' . strtoupper(\Str::random(8));
        return $data;
    }
}
