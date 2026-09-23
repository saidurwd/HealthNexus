<?php

namespace App\Support\Fhir;

use App\Models\Patient;

/**
 * Light-weight FHIR readiness mapping: produces a structurally-valid FHIR R4 Patient resource
 * from our internal model. This is a mapping utility, not a FHIR server — there is no
 * capability statement, no search/interaction layer, and no validation against the full FHIR
 * spec. It exists so a future integration engine has a known shape to start from.
 */
class PatientFhirMapper
{
    public function map(Patient $patient): array
    {
        return [
            'resourceType' => 'Patient',
            'id' => (string) $patient->id,
            'identifier' => $this->identifiers($patient),
            'active' => $patient->status === 'active',
            'name' => [[
                'use' => 'official',
                'family' => $patient->last_name,
                'given' => array_values(array_filter([$patient->first_name, $patient->middle_name])),
                'text' => $patient->display_name,
            ]],
            'telecom' => array_values(array_filter([
                $patient->phone ? ['system' => 'phone', 'value' => $patient->phone, 'use' => 'mobile'] : null,
                $patient->email ? ['system' => 'email', 'value' => $patient->email] : null,
            ])),
            'gender' => $this->gender($patient),
            'birthDate' => $patient->date_of_birth?->format('Y-m-d'),
            'deceasedDateTime' => $patient->deceased_at?->toIso8601String(),
            'address' => $patient->addresses->map(fn ($address) => [
                'use' => match ($address->address_type) {
                    'permanent', 'present' => 'home',
                    'work' => 'work',
                    default => 'billing',
                },
                'line' => array_values(array_filter([$address->line1, $address->line2])),
                'city' => $address->city,
                'district' => $address->district,
                'state' => $address->state?->name,
                'postalCode' => $address->postal_code,
                'country' => $address->country?->name,
            ])->all(),
            'maritalStatus' => $patient->maritalStatus ? [
                'text' => $patient->maritalStatus->name,
            ] : null,
            'contact' => $patient->guardians->map(fn ($guardian) => [
                'relationship' => [['text' => $guardian->relationship]],
                'name' => ['text' => $guardian->name],
                'telecom' => array_values(array_filter([
                    $guardian->phone ? ['system' => 'phone', 'value' => $guardian->phone] : null,
                    $guardian->email ? ['system' => 'email', 'value' => $guardian->email] : null,
                ])),
            ])->all(),
        ];
    }

    private function identifiers(Patient $patient): array
    {
        $identifiers = [];

        if ($patient->enterprise_patient_no) {
            $identifiers[] = [
                'use' => 'usual',
                'type' => ['text' => 'Enterprise Patient Number'],
                'value' => $patient->enterprise_patient_no,
            ];
        }

        if ($patient->national_identifier) {
            $identifiers[] = [
                'use' => 'official',
                'type' => ['text' => 'National Identifier'],
                'value' => $patient->national_identifier,
            ];
        }

        foreach ($patient->identifiers as $identifier) {
            $identifiers[] = [
                'type' => ['text' => $identifier->identifier_type],
                'value' => $identifier->identifier_value,
                'assigner' => $identifier->issuing_authority ? ['display' => $identifier->issuing_authority] : null,
            ];
        }

        return $identifiers;
    }

    private function gender(Patient $patient): ?string
    {
        return match ($patient->sex) {
            'M' => 'male',
            'F' => 'female',
            'O' => 'other',
            default => 'unknown',
        };
    }
}
