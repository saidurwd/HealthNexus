<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterAmendmentRequest extends FormRequest
{
    // approved_by/approved_at are deliberately not accepted here — approval only happens via the
    // separate approveAmendment action, which requires a different actor than the creator.
    public function rules(): array
    {
        return [
            'amendment_type' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:2000'],
            'content' => ['required', 'string', 'max:5000'],
        ];
    }
}
