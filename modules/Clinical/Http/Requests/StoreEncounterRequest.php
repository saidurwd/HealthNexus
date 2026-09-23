<?php

namespace Modules\Clinical\Http\Requests;

use App\Services\TenantContextResolver;
use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * encounter_type_id (the configurable master-data table) is the primary field now —
     * encounter_type stays as an optional free-text fallback for display/legacy compatibility,
     * it is no longer restricted to a hardcoded enum. attending_doctor_id was previously
     * validated here but silently dropped on create() because it was never in Encounter's
     * $fillable — provider_id is the column actually used throughout the app (including the
     * Phase 2 Provider abstraction).
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'encounter_type_id' => ['nullable', 'integer', 'exists:encounter_types,id'],
            'encounter_type' => ['nullable', 'string', 'max:100'],
            'provider_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'priority' => ['nullable', 'string', 'in:routine,urgent,stat'],
            'source' => ['nullable', 'string', 'max:100'],
            'reason_for_visit' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        $data['company_id'] = app(TenantContextResolver::class)->getCompanyId();

        return $data;
    }
}
