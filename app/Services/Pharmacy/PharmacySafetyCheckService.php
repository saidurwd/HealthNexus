<?php

namespace App\Services\Pharmacy;

use App\Contracts\Pharmacy\DrugInformationProviderInterface;
use App\Models\Patient;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacySafetyAlert;
use Illuminate\Support\Collection;

/**
 * Alert-not-block medication safety. Case-insensitive substring matching between the patient's
 * free-text PatientAllergy.substance entries (reused verbatim, no ingredient FK exists) and the
 * medication's generic/brand/ingredient names, plus a DrugInformationProviderInterface call for
 * real interaction data (null by default — no interaction data ships in this phase). Never
 * blocks dispensing itself; produces alert data the caller must persist and the pharmacist must
 * acknowledge or override before proceeding.
 */
class PharmacySafetyCheckService
{
    public function __construct(private readonly DrugInformationProviderInterface $drugInformation) {}

    /**
     * @param  Collection<int, PharmacyMedication>  $activeMedications
     * @return array<int, array{alert_type: string, severity: string, interacting_reference: ?string, explanation: string, recommended_action: ?string}>
     */
    public function check(Patient $patient, PharmacyMedication $medication, Collection $activeMedications = new Collection): array
    {
        $alerts = [];

        foreach ($this->matchAllergies($patient, $medication) as $substance) {
            $alerts[] = [
                'alert_type' => PharmacySafetyAlert::TYPE_ALLERGY,
                'severity' => PharmacySafetyAlert::SEVERITY_SEVERE,
                'interacting_reference' => $substance,
                'explanation' => "Patient has a documented allergy to \"{$substance}\", which may relate to {$medication->name}.",
                'recommended_action' => 'Confirm with the prescriber before dispensing; consider an alternative medication.',
            ];
        }

        foreach ($this->drugInformation->checkInteractions($medication, $activeMedications) as $interaction) {
            $alerts[] = [
                'alert_type' => PharmacySafetyAlert::TYPE_DRUG_INTERACTION,
                'severity' => $interaction['severity'] ?? PharmacySafetyAlert::SEVERITY_MODERATE,
                'interacting_reference' => $interaction['interacting_reference'] ?? null,
                'explanation' => $interaction['explanation'],
                'recommended_action' => $interaction['recommended_action'] ?? null,
            ];
        }

        if ($medication->batches()->where('is_quarantined', true)->exists()) {
            $alerts[] = [
                'alert_type' => PharmacySafetyAlert::TYPE_QUARANTINE,
                'severity' => PharmacySafetyAlert::SEVERITY_MODERATE,
                'interacting_reference' => null,
                'explanation' => "One or more batches of {$medication->name} are currently quarantined.",
                'recommended_action' => 'Verify only non-quarantined batches are selected for dispensing.',
            ];
        }

        return $alerts;
    }

    /**
     * @return array<int, string> matched allergy substance strings
     */
    private function matchAllergies(Patient $patient, PharmacyMedication $medication): array
    {
        $names = collect([$medication->name, $medication->generic?->generic_name, $medication->brand?->name])
            ->merge($medication->ingredients->pluck('generic.generic_name'))
            ->filter()
            ->map(fn (string $name) => mb_strtolower(trim($name)))
            ->filter(fn (string $name) => $name !== '');

        $matched = [];

        foreach ($patient->allergies()->where('is_active', true)->get() as $allergy) {
            $substance = mb_strtolower(trim($allergy->substance));

            if ($substance === '') {
                continue;
            }

            $isMatch = $names->contains(fn (string $name) => str_contains($name, $substance) || str_contains($substance, $name));

            if ($isMatch) {
                $matched[] = $allergy->substance;
            }
        }

        return $matched;
    }
}
