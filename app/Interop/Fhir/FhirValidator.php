<?php

namespace App\Interop\Fhir;

use App\Services\Interop\InteropPolicy;

/**
 * A small, honest validator: base R4 required elements for the resources we serve, plus whatever a partner's profile adds in `config/interop.php` (guideline override 2). It is NOT a
 * full FHIR validator and does not check code bindings or invariants; the Bangladesh Core IG rules must be loaded when DGHS supplies them (OD-18).
 */
class FhirValidator
{
    /** Required (1..*) elements in base R4. */
    private const BASE = [
        'Patient' => [], 'Practitioner' => [], 'Organization' => [],
        'Encounter' => ['status', 'class'], 'Appointment' => ['status', 'participant'], 'Condition' => ['subject'], 'Observation' => ['status', 'code'],
        'AllergyIntolerance' => ['patient'], 'Procedure' => ['status', 'subject'], 'ServiceRequest' => ['status', 'intent', 'subject'], 'DiagnosticReport' => ['status', 'code'],
        'ImagingStudy' => ['status', 'subject'], 'MedicationRequest' => ['status', 'intent', 'subject'], 'Invoice' => ['status'], 'Coverage' => ['status', 'beneficiary', 'payor'],
    ];

    public function __construct(private readonly InteropPolicy $policy) {}

    /** @return list<array{severity:string,code:string,diagnostics:string,expression:list<string>}> empty when the resource passes */
    public function validate(array $resource, string $profile = 'r4'): array
    {
        $type = $resource['resourceType'] ?? null;
        if (! is_string($type) || ! isset(self::BASE[$type])) {
            return [['severity' => 'error', 'code' => 'structure', 'diagnostics' => 'resourceType is missing or not one this server handles', 'expression' => ['resourceType']]];
        }
        $profiles = (array) $this->policy->get('profiles', []);
        $required = array_unique(array_merge(self::BASE[$type], $profile !== 'r4' ? (array) ($profiles[$profile][$type] ?? []) : []));
        $issues = [];
        foreach ($required as $element) {
            if (! $this->present($resource, $element)) {
                $issues[] = ['severity' => 'error', 'code' => 'required', 'diagnostics' => "$type.$element is required".($profile !== 'r4' ? " by profile $profile" : ''), 'expression' => ["$type.$element"]];
            }
        }
        if ($type === 'Patient' && isset($resource['birthDate']) && ! preg_match('/^\d{4}(-\d{2}(-\d{2})?)?$/', (string) $resource['birthDate'])) {
            $issues[] = ['severity' => 'error', 'code' => 'value', 'diagnostics' => 'Patient.birthDate must be YYYY, YYYY-MM or YYYY-MM-DD', 'expression' => ['Patient.birthDate']];
        }
        if ($type === 'Patient' && isset($resource['gender']) && ! in_array($resource['gender'], ['male', 'female', 'other', 'unknown'], true)) {
            $issues[] = ['severity' => 'error', 'code' => 'code-invalid', 'diagnostics' => 'Patient.gender must be male, female, other or unknown', 'expression' => ['Patient.gender']];
        }

        return $issues;
    }

    private function present(array $resource, string $element): bool
    {
        if ($element === 'medication' || $element === 'medication[x]') {
            return isset($resource['medicationCodeableConcept']) || isset($resource['medicationReference']);
        }
        $v = $resource[$element] ?? null;

        return $v !== null && $v !== [] && $v !== '';
    }
}
