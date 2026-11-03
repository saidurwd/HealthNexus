<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUCodeBlueCompleted;
use App\Events\Icu\ICUCodeBlueStarted;
use App\Events\Icu\ICUCriticalEventCreated;
use App\Models\Icu\IcuCriticalEvent;
use App\Models\Icu\IcuEpisode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class IcuCriticalEventService
{
    public function record(IcuEpisode $episode, array $data, User $user): IcuCriticalEvent
    {
        $event = IcuCriticalEvent::create([
            ...$data,
            'organization_id' => $episode->organization_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'status' => IcuCriticalEvent::STATUS_DRAFT,
            'reported_by' => $user->id,
        ]);

        event(new ICUCriticalEventCreated($event));

        if ($event->event_type === IcuCriticalEvent::TYPE_CODE_BLUE) {
            event(new ICUCodeBlueStarted($event));
        }

        return $event;
    }

    public function finalize(IcuCriticalEvent $event, User $user): IcuCriticalEvent
    {
        if ($event->status === IcuCriticalEvent::STATUS_FINAL) {
            throw ValidationException::withMessages(['event' => 'This critical event record is already finalized.']);
        }

        $event->update(['status' => IcuCriticalEvent::STATUS_FINAL, 'finalized_by' => $user->id, 'finalized_at' => now()]);

        if ($event->event_type === IcuCriticalEvent::TYPE_CODE_BLUE) {
            event(new ICUCodeBlueCompleted($event->fresh()));
        }

        return $event->refresh();
    }
}
