<?php

namespace App\Listeners\Icu;

use App\Events\Laboratory\CriticalResultDetected;
use App\Models\Icu\IcuEpisode;
use App\Services\Icu\IcuAlertService;

/**
 * When a critical lab result's order is linked to an active ICU episode's encounter, also
 * creates an ICU-scoped IcuCriticalAlert so ICU gets its own acknowledgement trail — never
 * duplicates LabCriticalResultAlert itself, no changes to Lab's code (Phase 11 plan decision #10).
 */
class CreateIcuAlertOnLabCriticalResult
{
    public function __construct(private readonly IcuAlertService $alerts) {}

    public function handle(CriticalResultDetected $event): void
    {
        $result = $event->result;
        $encounterId = $result->orderItem?->labOrder?->encounter_id;

        if (! $encounterId) {
            return;
        }

        $episode = IcuEpisode::query()
            ->where('encounter_id', $encounterId)
            ->whereNull('discharged_at')
            ->first();

        if (! $episode) {
            return;
        }

        $value = $result->numeric_value ?? $result->qualitative_value ?? $result->text_value ?? '';

        $this->alerts->trigger(
            $episode,
            'lab_critical_result',
            'high',
            (string) $value,
            null,
            $result,
        );
    }
}
