<?php

namespace App\Services\Laboratory;

use App\Contracts\Laboratory\LabAnalyzerAdapterInterface;
use App\Models\Laboratory\LabAnalyzer;

/**
 * Default no-op LabAnalyzerAdapterInterface binding — keeps the integration seam injectable
 * without shipping a real ASTM/HL7 protocol. Swap the binding in AppServiceProvider once a real
 * analyzer integration exists.
 */
class NullLabAnalyzerAdapter implements LabAnalyzerAdapterInterface
{
    public function importResults(LabAnalyzer $analyzer, array $rawPayload): array
    {
        return [];
    }
}
