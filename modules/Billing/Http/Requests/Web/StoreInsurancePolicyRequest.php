<?php

namespace Modules\Billing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreInsurancePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'insurance_provider_id' => ['required', 'integer', 'exists:billing_insurance_providers,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'policy_number' => ['required', 'string', 'max:255'],
            'member_number' => ['nullable', 'string', 'max:255'],
            'group_number' => ['nullable', 'string', 'max:255'],
            'authorization_reference' => ['nullable', 'string', 'max:255'],
            'coverage_limit' => ['required', 'numeric', 'min:0'],
            'copay_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'in:active,inactive,expired'],
        ];
    }
}
