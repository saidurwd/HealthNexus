<?php

namespace Modules\Radiology\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleExaminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modality_id' => ['required', 'integer', 'exists:radiology_modalities,id'],
            'technologist_id' => ['required', 'integer', 'exists:providers,id'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:240'],
        ];
    }
}
