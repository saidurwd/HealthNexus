<?php

namespace Modules\Patients\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'national_identifier' => ['nullable', 'string', 'max:255'],
            'enterprise_patient_no' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'sex' => ['nullable', 'in:M,F,O'],
            'blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'emergency_contact.name' => ['nullable', 'string', 'max:255'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:100'],
            'next_of_kin.*.name' => ['nullable', 'string', 'max:255'],
            'next_of_kin.*.phone' => ['nullable', 'string', 'max:50'],
            'next_of_kin.*.relationship' => ['nullable', 'string', 'max:100'],
            'identifiers.*.identifier_type' => ['nullable', 'string', 'max:255'],
            'identifiers.*.identifier_value' => ['nullable', 'string', 'max:255'],
            'identifiers.*.issuing_authority' => ['nullable', 'string', 'max:255'],
            'identifiers.*.issued_at' => ['nullable', 'date'],
            'identifiers.*.expires_at' => ['nullable', 'date'],
            'identifiers.*.is_primary' => ['boolean'],
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
