<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosisRequest extends FormRequest
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
            'code_type' => ['nullable', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:500'],
            'status' => ['in:provisional,confirmed,rule_out,resolved'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $data['company_id'] = app(\App\Services\TenantContextResolver::class)->getCompanyId();
        $data['branch_id'] = app(\App\Services\TenantContextResolver::class)->getBranchId();
        return $data;
    }
}
