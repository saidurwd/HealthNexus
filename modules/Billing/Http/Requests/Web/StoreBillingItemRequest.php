<?php

namespace Modules\Billing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingItemRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:billing_categories,id'],
            'tax_category_id' => ['nullable', 'integer', 'exists:billing_tax_categories,id'],
            'item_code' => ['required', 'string', 'max:50'],
            'item_type' => ['required', 'in:service,product,procedure,consultation,diagnostic,room,other'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit' => ['nullable', 'string', 'max:50'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_taxable' => ['boolean'],
            'is_clinically_chargeable' => ['boolean'],
            'clinical_event_type' => ['nullable', 'string', 'max:100'],
            'clinical_event_key' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
