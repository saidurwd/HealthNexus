<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class EnterLabResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numeric_value' => ['nullable', 'numeric'],
            'text_value' => ['nullable', 'string'],
            'qualitative_value' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
        ];
    }
}
