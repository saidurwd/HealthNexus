<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterNoteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'section' => ['nullable', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:5000'],
        ];
    }
}
