<?php

namespace App\Services\Icu;

use App\Events\Icu\ICUCriticalAlertAcknowledged;
use App\Events\Icu\ICUCriticalAlertEscalated;
use App\Events\Icu\ICUCriticalAlertTriggered;
use App\Models\Icu\IcuAlertEscalation;
use App\Models\Icu\IcuCriticalAlert;
use App\Models\Icu\IcuEpisode;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Follows Lab's LabCriticalResultAlert pattern (ack fields folded onto the alert row itself) —
 * not Nursing's split nursing_alerts/nursing_escalations design (Phase 11 plan decision #9).
 */
class IcuAlertService
{
    public function trigger(IcuEpisode $episode, string $rule, string $severity, ?string $triggeredValue = null, ?string $expectedRange = null, ?Model $source = null): IcuCriticalAlert
    {
        $alert = IcuCriticalAlert::create([
            'organization_id' => $episode->organization_id,
            'branch_id' => $episode->branch_id,
            'episode_id' => $episode->id,
            'rule' => $rule,
            'severity' => $severity,
            'triggered_value' => $triggeredValue,
            'expected_range' => $expectedRange,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'triggered_at' => now(),
            'escalation_status' => IcuCriticalAlert::ESCALATION_NONE,
        ]);

        event(new ICUCriticalAlertTriggered($alert));

        return $alert;
    }

    public function acknowledge(IcuCriticalAlert $alert, User $user, ?string $action = null): IcuCriticalAlert
    {
        DB::transaction(function () use ($alert, $user, $action) {
            $locked = IcuCriticalAlert::query()->lockForUpdate()->findOrFail($alert->id);

            if ($locked->acknowledged_at !== null) {
                throw ValidationException::withMessages(['alert' => 'This alert has already been acknowledged.']);
            }

            $locked->update(['acknowledged_by' => $user->id, 'acknowledged_at' => now(), 'action' => $action]);
        });

        $alert->refresh();
        event(new ICUCriticalAlertAcknowledged($alert));

        return $alert;
    }

    public function escalate(IcuCriticalAlert $alert, User $user, ?string $escalatedTo = null, ?User $escalatedToUser = null): IcuAlertEscalation
    {
        $escalation = $alert->escalations()->create([
            'escalated_to' => $escalatedTo,
            'escalated_to_user_id' => $escalatedToUser?->id,
            'escalated_by' => $user->id,
            'escalated_at' => now(),
        ]);

        $alert->update(['escalation_status' => IcuCriticalAlert::ESCALATION_ESCALATED]);

        event(new ICUCriticalAlertEscalated($escalation));

        return $escalation;
    }

    public function resolve(IcuAlertEscalation $escalation, User $user, ?string $actionTaken = null): IcuAlertEscalation
    {
        $escalation->update(['action_taken' => $actionTaken, 'resolved_at' => now(), 'resolved_by' => $user->id]);
        $escalation->alert->update(['escalation_status' => IcuCriticalAlert::ESCALATION_RESOLVED]);

        return $escalation->refresh();
    }
}
