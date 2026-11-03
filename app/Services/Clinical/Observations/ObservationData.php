<?php

namespace App\Services\Clinical\Observations;

use Carbon\CarbonInterface;

/** One measurement to record. `key` is a config('observations.vitals') key; use code/display for anything else. */
final class ObservationData
{
    public function __construct(
        public readonly int $organizationId,
        public readonly int $patientId,
        public readonly string $contextType,
        public readonly ?string $key = null,
        public readonly ?float $valueNumber = null,
        public readonly ?string $valueText = null,
        public readonly ?bool $valueBoolean = null,
        public readonly ?string $unitCode = null,
        public readonly ?CarbonInterface $effectiveAt = null,
        public readonly ?int $branchId = null,
        public readonly ?int $hospitalId = null,
        public readonly ?int $encounterId = null,
        public readonly ?int $contextId = null,
        public readonly ?string $code = null,
        public readonly ?string $display = null,
        public readonly string $codeSystem = 'loinc',
        public readonly ?string $deviceId = null,
        public readonly string $sourceModule = 'observation',
        public readonly ?string $sourceRecordId = null,
        public readonly string $status = 'final',
        public readonly ?float $referenceLow = null,
        public readonly ?float $referenceHigh = null,
    ) {}
}
