<?php

namespace Modules\Billing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingPriceListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'billing_item_id' => ['required', 'integer', 'exists:billing_items,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'provider_id' => ['nullable', 'integer', 'exists:users,id'],
            'corporate_id' => ['nullable', 'integer', 'exists:billing_corporates,id'],
            'insurance_policy_id' => ['nullable', 'integer', 'exists:billing_insurance_policies,id'],
            'patient_category' => ['nullable', 'string', 'max:50'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'minimum_price' => ['nullable', 'numeric', 'min:0'],
            'maximum_price' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['required', 'in:none,percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'tax_included' => ['boolean'],
            'priority' => ['required', 'integer', 'min:1'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['boolean'],
        ];
    }
}
