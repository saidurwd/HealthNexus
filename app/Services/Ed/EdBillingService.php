<?php

namespace App\Services\Ed;

use App\Contracts\Billing\ChargeEventPublisher;
use App\Models\Ed\EdResusEvent;
use App\Models\Ed\EdVisit;
use App\Models\EncounterProcedure;
use App\Models\User;
use App\Services\Billing\Events\ChargeEventData;

/**
 * What the department announces to Billing. Never a price: only a service code and a quantity (guideline 11.1). Every event is keyed on the
 * visit (or the procedure), so repeating a call, or a retry after Billing had no matching item yet, never charges twice.
 * Tests and scans, medicines and bed days are announced by Laboratory, Radiology, Pharmacy and the observation unit themselves.
 */
class EdBillingService
{
    public const VISIT = 'ED-VISIT';

    public const RESUS = 'ED-RESUS';

    public const PROCEDURE = 'ED-PROC';

    public function __construct(private readonly ChargeEventPublisher $charges) {}

    /** The visit fee, once, when the visit closes. A patient who left before being seen is not charged. */
    public function publishVisitClosed(EdVisit $visit, User $by, string $dispositionType): void
    {
        $seen = $visit->doctor_seen_at !== null || $visit->triaged_at !== null;
        $chargeable = $dispositionType !== 'lwbs' || $seen;

        $this->charges->publish(new ChargeEventData(
            organizationId: $visit->organization_id, branchId: $visit->branch_id, patientId: $visit->patient_id, module: 'ED', serviceCode: self::VISIT,
            sourceEventId: $visit->id, visitRef: $visit->visit_number, encounterId: $visit->encounter_id, performedAt: now(), performedBy: $by->id,
            providerId: null, sourceType: EdVisit::class, sourceId: $visit->id, isChargeable: $chargeable, noChargeReason: $chargeable ? null : 'Left without being seen',
        ));

        if (EdResusEvent::query()->where('visit_id', $visit->id)->exists()) {
            $this->charges->publish(new ChargeEventData(
                organizationId: $visit->organization_id, branchId: $visit->branch_id, patientId: $visit->patient_id, module: 'ED', serviceCode: self::RESUS,
                sourceEventId: $visit->id, visitRef: $visit->visit_number, encounterId: $visit->encounter_id, performedAt: now(), performedBy: $by->id,
                sourceType: EdVisit::class, sourceId: $visit->id,
            ));
        }
    }

    public function publishProcedure(EdVisit $visit, EncounterProcedure $procedure, User $by): void
    {
        $this->charges->publish(new ChargeEventData(
            organizationId: $visit->organization_id, branchId: $visit->branch_id, patientId: $visit->patient_id, module: 'ED', serviceCode: self::PROCEDURE,
            sourceEventId: $procedure->id, visitRef: $visit->visit_number, encounterId: $visit->encounter_id, performedAt: now(), performedBy: $by->id,
            sourceType: EncounterProcedure::class, sourceId: $procedure->id,
        ));
    }
}
