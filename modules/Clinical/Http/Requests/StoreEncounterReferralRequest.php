<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterReferralRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'referral_type' => ['required', 'string', 'max:100'],
            'referred_to' => ['nullable', 'string', 'max:255'],
            'referred_by' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
