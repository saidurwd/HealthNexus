<?php

namespace App\Contracts\Pharmacy;

use App\Models\Pharmacy\PharmacyMedication;
use Illuminate\Support\Collection;

/**
 * Seam for a future real drug-interaction/drug-information database integration. Phase 7 ships
 * no real interaction data beyond the no-op default (see NullDrugInformationProvider) — this
 * only exists so a future provider can plug in without touching PharmacySafetyCheckService
 * directly.
 */
interface DrugInformationProviderInterface
{
    /**
     * @param  Collection<int, PharmacyMedication>  $activeMedications
     * @return array<int, array{severity: string, interacting_reference: string, explanation: string, recommended_action: ?string}>
     */
    public function checkInteractions(PharmacyMedication $medication, Collection $activeMedications): array;
}
