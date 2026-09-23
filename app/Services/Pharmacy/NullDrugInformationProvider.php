<?php

namespace App\Services\Pharmacy;

use App\Contracts\Pharmacy\DrugInformationProviderInterface;
use App\Models\Pharmacy\PharmacyMedication;
use Illuminate\Support\Collection;

/**
 * Default no-op DrugInformationProviderInterface binding — keeps the integration seam injectable
 * without shipping a real drug-interaction database. Swap the binding in AppServiceProvider once
 * a real provider exists.
 */
class NullDrugInformationProvider implements DrugInformationProviderInterface
{
    public function checkInteractions(PharmacyMedication $medication, Collection $activeMedications): array
    {
        return [];
    }
}
