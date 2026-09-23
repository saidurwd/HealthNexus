<?php

namespace Modules\Clinical\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * status here is only ever consumed by the controller to route through
     * EncounterLifecycleService::moveTo() — the transition graph itself is what actually
     * enforces which changes are legal, this list is just the full set of valid status values.
     */
    public function rules(): array
    {
        return [
            'encounter_type' => ['sometimes', 'string', 'max:100'],
            'encounter_type_id' => ['nullable', 'integer', 'exists:encounter_types,id'],
            'provider_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'priority' => ['sometimes', 'string', 'in:routine,urgent,stat'],
            'reason_for_visit' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:draft,registered,waiting,in_progress,paused,completed,cancelled,transferred,locked,amended'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
