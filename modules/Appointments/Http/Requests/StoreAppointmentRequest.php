<?php

namespace Modules\Appointments\Http\Requests;

use App\Services\TenantContextResolver;
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
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'slot_id' => ['nullable', 'integer', 'exists:appointment_slots,id'],
            'appointment_type_id' => ['nullable', 'integer', 'exists:appointment_types,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'room_id' => ['nullable', 'integer', 'exists:appointment_rooms,id'],
            // required_without:slot_id — a slot already carries its own date/time; a free-text
            // booking (no pre-generated slot, e.g. certain walk-ins) must supply them directly.
            'appointment_date' => ['required_without:slot_id', 'nullable', 'date'],
            'appointment_time' => ['required_without:slot_id', 'nullable', 'date_format:H:i'],
            'type' => ['string', 'in:scheduled,walk_in,followup'],
            'source' => ['string', 'in:online,offline,referral,phone,walkin,reception,call_center,patient_portal,doctor,mobile_app,website,api,corporate'],
            'reason' => ['nullable', 'string'],
            'priority' => ['string', 'in:routine,urgent,stat'],
            'is_walk_in' => ['boolean'],
            'is_telemedicine' => ['boolean'],
            'referral_source' => ['nullable', 'string', 'max:255'],
            'referred_by' => ['nullable', 'string', 'max:255'],
            'referral_organization' => ['nullable', 'string', 'max:255'],
            'referral_reference' => ['nullable', 'string', 'max:255'],
            'allow_overbooking' => ['boolean'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        $data['company_id'] = app(TenantContextResolver::class)->getCompanyId();
        $data['branch_id'] = app(TenantContextResolver::class)->getBranchId();
        $data['created_by'] = $this->user()->id;

        unset($data['allow_overbooking']);

        return $data;
    }
}
