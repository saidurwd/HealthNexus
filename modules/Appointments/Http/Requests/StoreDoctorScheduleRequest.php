<?php

namespace Modules\Appointments\Http\Requests;

use App\Services\TenantContextResolver;
use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'integer', 'exists:users,id'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'room_id' => ['nullable', 'integer', 'exists:appointment_rooms,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'day_of_week' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'slot_duration_minutes' => ['integer', 'min:5', 'max:120'],
            'buffer_minutes' => ['integer', 'min:0', 'max:60'],
            'break_start_time' => ['nullable', 'date_format:H:i'],
            'break_end_time' => ['nullable', 'date_format:H:i', 'after:break_start_time'],
            'default_capacity_per_slot' => ['integer', 'min:1', 'max:50'],
            'overbooking_limit' => ['integer', 'min:0', 'max:20'],
            'is_publish_slots' => ['boolean'],
            'slot_date' => ['nullable', 'date'],
            'generate_days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $data['company_id'] = app(TenantContextResolver::class)->getCompanyId();
        $data['branch_id'] = app(TenantContextResolver::class)->getBranchId();

        return $data;
    }
}
