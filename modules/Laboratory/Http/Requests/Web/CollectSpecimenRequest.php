<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class CollectSpecimenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'specimen_type_id' => ['nullable', 'integer', 'exists:lab_specimen_types,id'],
            'container_type_id' => ['nullable', 'integer', 'exists:lab_container_types,id'],
            'storage_location' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'order_item_ids' => ['nullable', 'array'],
            'order_item_ids.*' => ['integer', 'exists:lab_order_items,id'],
        ];
    }
}
