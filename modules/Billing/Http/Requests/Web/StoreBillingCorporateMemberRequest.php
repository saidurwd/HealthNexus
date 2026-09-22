<?php

namespace Modules\Billing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingCorporateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'corporate_contract_id' => ['nullable', 'integer', 'exists:billing_corporate_contracts,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'member_number' => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'string', 'max:100'],
            'relationship' => ['required', 'in:employee,dependent,spouse,other'],
            'is_active' => ['boolean'],
        ];
    }
}
