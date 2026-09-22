<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterInstructionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'instruction_type' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:5000'],
        ];
    }
}
