<?php

namespace Modules\Billing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'encounter_id' => ['nullable', 'integer', 'exists:encounters,id'],
            'invoice_type' => ['required', 'in:opd,ipd,diagnostic,pharmacy,procedure,corporate,insurance,advance,other'],
            'corporate_id' => ['nullable', 'integer', 'exists:billing_corporates,id'],
            'insurance_policy_id' => ['nullable', 'integer', 'exists:billing_insurance_policies,id'],
            'patient_category' => ['nullable', 'string', 'max:50'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
