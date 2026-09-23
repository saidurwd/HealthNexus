<?php

namespace App\Contracts\Laboratory;

use App\Models\Laboratory\LabAnalyzer;

/**
 * Seam for a future real analyzer integration (ASTM/HL7/vendor API/file exchange). Phase 5
 * ships no working protocol beyond the no-op default (see NullLabAnalyzerAdapter) — this only
 * exists so a future adapter can plug in without touching LabTest/LabResult models directly.
 */
interface LabAnalyzerAdapterInterface
{
    /**
     * Normalizes and imports raw analyzer output into LIS results.
     *
     * @param  array<string, mixed>  $rawPayload
     * @return array<int, array{lab_order_item_id: int, numeric_value: ?string, text_value: ?string}>
     */
    public function importResults(LabAnalyzer $analyzer, array $rawPayload): array;
}
