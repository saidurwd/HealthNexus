<?php

namespace Modules\Audit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveSecurityEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('security.event.resolve');
    }

    public function rules(): array
    {
        return [
            'resolution_note' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
