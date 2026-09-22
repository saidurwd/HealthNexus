<?php

namespace Modules\Billing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillingPriceListRequest extends FormRequest
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
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', 'in:active,inactive'],
            'priority' => ['required', 'integer', 'min:1'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
