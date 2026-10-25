<?php

namespace App\Services\Clinical\Alerts;

use App\Events\Clinical\AlertAcknowledged;
use App\Events\Clinical\AlertEscalated;
use App\Events\Clinical\AlertRaised;
use App\Events\Clinical\AlertResolved;
use App\Models\Clinical\ClinicalAlert;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One engine for raise → deliver → acknowledge → escalate → resolve (guideline 10.5, decision D-09).
 * Modules keep their own alert types and tables for now; they raise here too, and the service is the single place
 * that answers "what is still unacknowledged?".
 */
class AlertService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly SettingsService $settings,
    ) {}

    public function raise(AlertRequest $request): ClinicalAlert
    {
        if (! in_array($request->severity, config('alerts.severities'), true)) {
            throw ValidationException::withMessages(['severity' => "Unknown severity [{$request->severity}]."]);
        }

        return DB::transaction(function () use ($request) {
            if ($existing = $this->existing($request)) {
                return $existing;
            }

            $policy = $request->escalationPolicy ?? ($request->severity === 'info' ? null : $request->severity);
            $ladder = $request->ackRequired && $policy ? $this->ladder($policy) : [];
            $raisedAt = now();

            $alert = ClinicalAlert::create([
                'organization_id' => $request->organizationId, 'hospital_id' => $request->hospitalId, 'branch_id' => $request->branchId,
                'patient_id' => $request->patientId, 'category' => $request->category, 'severity' => $request->severity,
                'message_key' => $request->messageKey, 'message' => $request->message, 'source_module' => $request->sourceModule,
                'source_type' => $request->sourceType, 'source_id' => $request->sourceId !== null ? (string) $request->sourceId : null,
                'ack_required' => $request->ackRequired, 'escalation_policy' => $policy, 'escalation_level' => 0,
                'next_escalation_at' => $ladder !== [] ? $raisedAt->copy()->addMinutes($ladder[0]['after_minutes']) : null,
                'dedup_key' => $request->dedupKey, 'raised_at' => $raisedAt, 'raised_by' => $request->raisedBy,
            ]);

            $users = User::query()
                ->whereIn('id', $request->recipientUserIds)
                ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $request->organizationId))
                ->get();

            $alert->refresh(); // pick up column defaults (status, public_id)
            $this->deliver($alert, $users, 0);

            AlertRaised::dispatch($alert->id, $alert->organization_id, $alert->category, $alert->severity);

            return $alert;
        });
    }

    public function acknowledge(ClinicalAlert $alert, User $actor, ?string $note = null, bool $readBack = false): ClinicalAlert
    {
        return DB::transaction(function () use ($alert, $actor, $note, $readBack) {
            $locked = $this->lockOpen($alert, 'acknowledged');

            if (in_array($locked->category, config('alerts.read_back_required_categories', []), true) && ! $readBack) {
                throw ValidationException::withMessages(['read_back' => 'Read-back confirmation is required for this alert.']);
            }

            $locked->update([
                'status' => ClinicalAlert::STATUS_ACKNOWLEDGED, 'acknowledged_at' => now(), 'acknowledged_by' => $actor->id,
                'read_back_confirmed' => $readBack, 'acknowledge_note' => $note, 'next_escalation_at' => null,
            ]);

            AlertAcknowledged::dispatch($locked->id, $locked->organization_id, $locked->category, $locked->severity);

            return $locked;
        });
    }

    /**
     * Mirrors an acknowledgement made in a module's own screen (lab, ICU, nursing) so the Alert Service stays the one
     * place that knows what is still open. The module's own flow has no read-back step, so none is recorded.
     */
    public function acknowledgeBySource(string $sourceModule, string $sourceType, string|int $sourceId, User $actor, ?string $note = null): ?ClinicalAlert
    {
        $alert = ClinicalAlert::query()
            ->where(['source_module' => $sourceModule, 'source_type' => $sourceType, 'source_id' => (string) $sourceId])
            ->where('status', ClinicalAlert::STATUS_OPEN)->first();

        if (! $alert) {
            return null;
        }

        return DB::transaction(function () use ($alert, $actor, $note) {
            $locked = $this->lockOpen($alert, 'acknowledged');
            $locked->update([
                'status' => ClinicalAlert::STATUS_ACKNOWLEDGED, 'acknowledged_at' => now(), 'acknowledged_by' => $actor->id,
                'acknowledge_note' => $note, 'next_escalation_at' => null,
            ]);

            AlertAcknowledged::dispatch($locked->id, $locked->organization_id, $locked->category, $locked->severity);

            return $locked;
        });
    }

    public function resolve(ClinicalAlert $alert, User $actor, ?string $note = null): ClinicalAlert
    {
        return DB::transaction(function () use ($alert, $actor, $note) {
            $locked = ClinicalAlert::query()->lockForUpdate()->findOrFail($alert->id);

            if (! in_array($locked->status, [ClinicalAlert::STATUS_OPEN, ClinicalAlert::STATUS_ACKNOWLEDGED], true)) {
                throw ValidationException::withMessages(['alert' => 'This alert is already closed.']);
            }

            $locked->update([
                'status' => ClinicalAlert::STATUS_RESOLVED, 'resolved_at' => now(), 'resolved_by' => $actor->id,
                'resolve_note' => $note, 'next_escalation_at' => null,
            ]);

            AlertResolved::dispatch($locked->id, $locked->organization_id, $locked->category, $locked->severity);

            return $locked;
        });
    }

    /** E.g. a clinician overrides an allergy warning: the reason is mandatory and the alert is closed as `overridden`. */
    public function override(ClinicalAlert $alert, User $actor, string $reason): ClinicalAlert
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'An override reason is required.']);
        }

        return DB::transaction(function () use ($alert, $actor, $reason) {
            $locked = $this->lockOpen($alert, 'overridden');

            $locked->update([
                'status' => ClinicalAlert::STATUS_OVERRIDDEN, 'override_reason' => $reason,
                'acknowledged_at' => now(), 'acknowledged_by' => $actor->id, 'next_escalation_at' => null,
            ]);

            AlertResolved::dispatch($locked->id, $locked->organization_id, $locked->category, $locked->severity);

            return $locked;
        });
    }

    /**
     * Moves every overdue open alert one step up its ladder. Safe to run every minute and from several workers:
     * each alert is locked and its level re-checked.
     *
     * @return int alerts escalated
     */
    public function escalateDue(): int
    {
        $count = 0;

        ClinicalAlert::query()
            ->where('status', ClinicalAlert::STATUS_OPEN)->where('ack_required', true)
            ->whereNotNull('next_escalation_at')->where('next_escalation_at', '<=', now())
            ->orderBy('id')->pluck('id')
            ->each(function (int $id) use (&$count) {
                $count += DB::transaction(fn () => $this->escalateOne($id)) ? 1 : 0;
            });

        return $count;
    }

    private function escalateOne(int $id): bool
    {
        $alert = ClinicalAlert::query()->lockForUpdate()->find($id);

        if (! $alert || $alert->status !== ClinicalAlert::STATUS_OPEN || $alert->next_escalation_at === null || $alert->next_escalation_at->isFuture()) {
            return false;
        }

        $ladder = $this->ladder($alert->escalation_policy);
        $step = $ladder[$alert->escalation_level] ?? null;

        if ($step === null) {
            $alert->update(['next_escalation_at' => null]);

            return false;
        }

        $level = $alert->escalation_level + 1;
        $users = User::query()
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $alert->organization_id))
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $step['roles']))
            ->get();

        $notified = $this->deliver($alert, $users, $level);

        DB::table('clinical_alert_escalations')->insert([
            'alert_id' => $alert->id, 'level' => $level, 'target_roles' => implode(',', $step['roles']),
            'recipients_notified' => $notified, 'escalated_at' => now(),
        ]);

        $next = $ladder[$level] ?? null;
        $alert->update([
            'escalation_level' => $level,
            'next_escalation_at' => $next ? $alert->raised_at->copy()->addMinutes($next['after_minutes']) : null,
        ]);

        AlertEscalated::dispatch($alert->id, $alert->organization_id, $alert->category, $alert->severity);

        return true;
    }

    /** @return int recipients newly notified */
    private function deliver(ClinicalAlert $alert, Collection $users, int $level): int
    {
        $new = 0;
        $channels = config('alerts.channels');

        foreach ($users as $user) {
            $recipient = $alert->recipients()->firstOrCreate(
                ['user_id' => $user->id],
                ['level' => $level, 'channels' => implode(',', $channels), 'notified_at' => now()],
            );

            if ($recipient->wasRecentlyCreated) {
                $this->notifications->send($user, 'clinical_alert', ['message' => $alert->message, 'severity' => $alert->severity], $channels);
                $new++;
            }
        }

        return $new;
    }

    private function existing(AlertRequest $request): ?ClinicalAlert
    {
        if ($request->sourceType !== null && $request->sourceId !== null) {
            $bySource = ClinicalAlert::query()->where([
                'source_module' => $request->sourceModule, 'source_type' => $request->sourceType,
                'source_id' => (string) $request->sourceId, 'category' => $request->category,
            ])->first();

            if ($bySource) {
                return $bySource;
            }
        }

        if ($request->dedupKey !== null) {
            return ClinicalAlert::query()
                ->where('organization_id', $request->organizationId)->where('dedup_key', $request->dedupKey)
                ->where('status', ClinicalAlert::STATUS_OPEN)
                ->where('raised_at', '>=', now()->subMinutes((int) config('alerts.dedup_window_minutes')))
                ->first();
        }

        return null;
    }

    private function lockOpen(ClinicalAlert $alert, string $verb): ClinicalAlert
    {
        $locked = ClinicalAlert::query()->lockForUpdate()->findOrFail($alert->id);

        if (! $locked->isOpen()) {
            throw ValidationException::withMessages(['alert' => "This alert can no longer be {$verb}; it is {$locked->status}."]);
        }

        return $locked;
    }

    /** @return list<array{after_minutes: int, roles: list<string>}> */
    private function ladder(?string $policy): array
    {
        $policies = $this->settings->get('alerts.escalation_policies');
        $policies = is_array($policies) ? $policies : (is_string($policies) ? json_decode($policies, true) : null);
        $policies ??= [];

        return array_values($policies[$policy] ?? config("alerts.policies.$policy") ?? []);
    }
}
