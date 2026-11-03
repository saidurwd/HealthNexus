<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUVentilatorChanged;
use App\Events\Icu\ICUVentilatorStarted;
use App\Events\Icu\ICUVentilatorStopped;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuVentilatorEvent;
use App\Models\User;

/**
 * Append-only — each call writes one full-snapshot row; "current ventilator settings" is always
 * the latest row for the episode (Phase 11 plan decision #13).
 */
class IcuVentilatorService
{
    public function recordEvent(IcuEpisode $episode, array $data, User $user): IcuVentilatorEvent
    {
        $event = IcuVentilatorEvent::create([
            ...$data,
            'organization_id' => $episode->organization_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'recorded_at' => $data['recorded_at'] ?? now(),
            'recorded_by' => $user->id,
        ]);

        match ($event->event_type) {
            IcuVentilatorEvent::TYPE_INITIATED => event(new ICUVentilatorStarted($event)),
            IcuVentilatorEvent::TYPE_EXTUBATED => event(new ICUVentilatorStopped($event)),
            default => event(new ICUVentilatorChanged($event)),
        };

        return $event;
    }
}
