<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'doctor_id' => ['required', 'integer', 'exists:users,id'],
            'slot_id' => ['nullable', 'integer', 'exists:appointment_slots,id'],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'type' => ['string', 'in:scheduled,walk_in'],
            'source' => ['string', 'in:online,offline,referral'],
            'reason' => ['nullable', 'string'],
            'priority' => ['string', 'in:routine,urgent,stat'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        $data['company_id'] = app(\App\Services\TenantContextResolver::class)->getCompanyId();
        $data['branch_id'] = app(\App\Services\TenantContextResolver::class)->getBranchId();
        $data['created_by'] = $this->user()->id;

        return $data;
    }
}
