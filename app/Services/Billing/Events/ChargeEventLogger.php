<?php

namespace App\Services\Billing\Events;

use App\Models\Billing\BillingCharge;
use App\Models\Billing\BillingItem;
use App\Models\Billing\ChargeEventLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Adapter (guideline 24.1 H-05): modules built before the Charge Event Publisher still call ChargeService directly.
 * Every such charge is mirrored into charge_event_log under the contract key format, so the reconciliation report and
 * duplicate counts cover all modules, not just the new ones.
 */
class ChargeEventLogger
{
    private const MODULE_BY_NAMESPACE = [
        'Laboratory' => 'LAB', 'Radiology' => 'RAD', 'Pharmacy' => 'PHM', 'Ipd' => 'IPD', 'Nursing' => 'NUR',
        'Ot' => 'OT', 'Icu' => 'ICU', 'Billing' => 'BIL',
    ];

    public function recordAdapted(BillingCharge $charge, BillingItem $item, Model $source, bool $repeat): ChargeEventLog
    {
        $module = $this->moduleFor($source);
        $visitRef = $charge->encounter_id;
        $key = ChargeEventData::key($module, $visitRef, $item->item_code, $source->getKey());

        $log = ChargeEventLog::query()->firstOrCreate(
            ['organization_id' => $charge->organization_id, 'idempotency_key' => $key],
            [
                'branch_id' => $charge->branch_id, 'module' => $module, 'visit_ref' => (string) ($visitRef ?? ''), 'service_code' => $item->item_code,
                'patient_id' => $charge->patient_id, 'encounter_id' => $charge->encounter_id, 'quantity' => $charge->quantity,
                'source_type' => $source->getMorphClass(), 'source_id' => (string) $source->getKey(), 'performed_at' => $charge->charged_at,
                'outcome' => ChargeEventLog::OUTCOME_ACCEPTED, 'billing_charge_id' => $charge->id,
            ],
        );

        if ($repeat && ! $log->wasRecentlyCreated) {
            $log->increment('publish_count');
        }

        return $log;
    }

    private function moduleFor(Model $source): string
    {
        foreach (self::MODULE_BY_NAMESPACE as $segment => $code) {
            if (str_contains(get_class($source), "\\$segment\\")) {
                return $code;
            }
        }

        return 'OPD'; // Encounter, ClinicalOrder and other Phase 3 records
    }
}
