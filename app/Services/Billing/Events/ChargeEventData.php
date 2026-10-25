<?php

namespace App\Services\Billing\Events;

use Carbon\CarbonInterface;

/**
 * One billable occurrence. Carries a service code and a quantity — never a price.
 * Key format: <MODULE>:<visit_ref>:<service_code>:<source_event_id> (modules: OPD LAB RAD PHM IPD BED NUR OT ICU ED BLD INS INV ...).
 */
final class ChargeEventData
{
    public readonly string $idempotencyKey;

    public function __construct(
        public readonly int $organizationId,
        public readonly int $branchId,
        public readonly int $patientId,
        public readonly string $module,
        public readonly string $serviceCode,
        public readonly string|int $sourceEventId,
        public readonly string|int|null $visitRef = null,
        public readonly ?int $hospitalId = null,
        public readonly ?int $encounterId = null,
        public readonly ?int $admissionId = null,
        public readonly float|int|string $quantity = 1,
        public readonly ?CarbonInterface $performedAt = null,
        public readonly ?int $performedBy = null,
        public readonly ?int $providerId = null,
        public readonly ?int $departmentId = null,
        public readonly ?string $sourceType = null,
        public readonly string|int|null $sourceId = null,
        public readonly string $billingPartyHint = 'patient',
        public readonly bool $isChargeable = true,
        public readonly ?string $noChargeReason = null,
    ) {
        $this->idempotencyKey = self::key($module, $visitRef ?? $encounterId ?? $admissionId, $serviceCode, $sourceEventId);
    }

    public static function key(string $module, string|int|null $visitRef, string $serviceCode, string|int $sourceEventId): string
    {
        return strtoupper($module).':'.($visitRef ?? '-').':'.$serviceCode.':'.$sourceEventId;
    }
}
