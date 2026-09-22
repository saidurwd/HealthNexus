<?php

namespace Modules\Billing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class RequestAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:billing_invoices,id'],
            'type' => ['required', 'in:discount,price,credit,debit,write_off'],
            'original_value' => ['required', 'numeric'],
            'new_value' => ['required', 'numeric'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
