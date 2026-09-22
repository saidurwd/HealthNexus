<?php

namespace Modules\Billing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillingCorporateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'payment_terms_days' => ['required', 'integer', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,weekly,biweekly,on_demand'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
