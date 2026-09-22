<?php

namespace Modules\Billing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashierSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'counter_id' => ['nullable', 'integer', 'exists:departments,id'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ];
    }
}
