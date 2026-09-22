<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClinicalOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_type' => ['required', 'string', 'max:100'],
            'priority' => ['nullable', 'string', 'in:routine,urgent,stat'],
            'provider_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.item_code' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
