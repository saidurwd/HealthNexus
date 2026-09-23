<?php

namespace Modules\Patients\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_type_id' => ['nullable', 'integer', 'exists:patient_types,id'],
            'gender_id' => ['nullable', 'integer', 'exists:genders,id'],
            'marital_status_id' => ['nullable', 'integer', 'exists:marital_statuses,id'],
            'nationality_id' => ['nullable', 'integer', 'exists:countries,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'dob_unknown' => ['boolean'],
            'estimated_age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'estimated_age_unit' => ['nullable', 'in:years,months,days'],
            'sex' => ['nullable', 'in:M,F,O'],
            'blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'rh_factor' => ['nullable', 'in:positive,negative,unknown'],
            'is_temporary' => ['boolean'],
            'is_unknown' => ['boolean'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'status' => ['string', 'in:active,inactive,deceased'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        if (! empty($data['emergency_contact']['name'] ?? null)) {
            $data['contacts'][] = [
                'name' => $data['emergency_contact']['name'],
                'phone' => $data['emergency_contact']['phone'] ?? null,
                'relationship' => $data['emergency_contact']['relationship'] ?? null,
                'email' => null,
                'address' => null,
                'is_emergency' => true,
            ];
            unset($data['emergency_contact']);
        }

        if (! empty($data['next_of_kin'] ?? [])) {
            foreach ($data['next_of_kin'] as $kin) {
                if (! empty($kin['name'])) {
                    $data['contacts'][] = [
                        'name' => $kin['name'],
                        'phone' => $kin['phone'] ?? null,
                        'relationship' => $kin['relationship'] ?? null,
                        'email' => null,
                        'address' => null,
                        'is_emergency' => false,
                    ];
                }
            }
            unset($data['next_of_kin']);
        }

        return $data;
    }
}
