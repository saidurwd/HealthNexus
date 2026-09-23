<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class RejectSpecimenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'in:'.implode(',', array_keys(config('laboratory.rejection_reasons')))],
            'notes' => ['nullable', 'string'],
        ];
    }
}
