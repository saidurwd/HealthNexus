<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'max:100'],
            'file_name' => ['required', 'string', 'max:255'],
            'file_path' => ['required', 'string', 'max:500'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'file_size' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
