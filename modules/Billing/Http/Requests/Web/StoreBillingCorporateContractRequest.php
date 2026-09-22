<?php

namespace Modules\Billing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingCorporateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_list_id' => ['nullable', 'integer', 'exists:billing_price_lists,id'],
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', 'in:none,percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'payment_terms_days' => ['required', 'integer', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
