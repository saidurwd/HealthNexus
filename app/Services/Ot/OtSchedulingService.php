<?php

namespace App\Services\Ot;

use App\Events\Ot\SurgeryCancelled;
use App\Events\Ot\SurgeryPostponed;
use App\Events\Ot\SurgeryRescheduled;
use App\Events\Ot\SurgeryScheduled;
use App\Models\Ot\OtSurgery;
use App\Models\Ot\OtSurgeryScheduleEvent;
use App\Models\Ot\OtTheatre;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The concurrency-critical core of Phase 10 (plan decision #12). Locks the target OtTheatre row
 * itself before checking for an overlapping booking — exactly like IpdBedAllocationService locks
 * the bed row before allocating it — so a concurrent scheduling attempt against the same theatre
 * is serialized rather than racing, which is what the mandatory "two users, same theatre, same
 * time, only one succeeds" test proves. Surgeon/anesthetist overlap is a secondary, best-effort
 * check performed under the same lock, since the theatre is the one genuinely contended resource.
 */
class OtSchedulingService
{
    public function schedule(OtSurgery $surgery, array $data, User $user): OtSurgery
    {
        return DB::transaction(function () use ($surgery, $data, $user) {
            $theatre = OtTheatre::query()->whereKey($data['theatre_id'])->lockForUpdate()->firstOrFail();

            if (! $theatre->is_active || $theatre->status === OtTheatre::STATUS_MAINTENANCE || $theatre->status === OtTheatre::STATUS_BLOCKED) {
                throw ValidationException::withMessages(['theatre' => "Theatre '{$theatre->theatre_code}' is not available for scheduling."]);
            }

            $start = $data['scheduled_start_at'];
            $end = $data['scheduled_end_at'];

            $this->assertNoTheatreConflict($theatre->id, $start, $end, $surgery->id);

            if (! empty($data['primary_surgeon_id'])) {
                $this->assertNoProviderConflict($data['primary_surgeon_id'], $start, $end, $surgery->id, 'surgeon');
            }

            if (! empty($data['anesthetist_id'])) {
                $this->assertNoProviderConflict($data['anesthetist_id'], $start, $end, $surgery->id, 'anesthetist');
            }

            $surgery->update(array_filter([
                'theatre_id' => $theatre->id,
                'scheduled_date' => $data['scheduled_date'] ?? $start->toDateString(),
                'scheduled_start_at' => $start,
                'scheduled_end_at' => $end,
                'primary_surgeon_id' => $data['primary_surgeon_id'] ?? $surgery->primary_surgeon_id,
                'assistant_surgeon_id' => $data['assistant_surgeon_id'] ?? $surgery->assistant_surgeon_id,
                'anesthetist_id' => $data['anesthetist_id'] ?? $surgery->anesthetist_id,
            ], fn ($v) => $v !== null));

            app(OtSurgeryLifecycleService::class)->transitionTo($surgery, OtSurgery::STATUS_SCHEDULED);

            OtSurgeryScheduleEvent::create([
                'surgery_id' => $surgery->id,
                'theatre_id' => $theatre->id,
                'change_type' => OtSurgeryScheduleEvent::TYPE_SCHEDULED,
                'scheduled_date' => $surgery->scheduled_date,
                'scheduled_start_at' => $start,
                'scheduled_end_at' => $end,
                'changed_by' => $user->id,
                'changed_at' => now(),
            ]);

            event(new SurgeryScheduled($surgery->fresh()));

            return $surgery->fresh();
        });
    }

    public function reschedule(OtSurgery $surgery, array $data, User $user): OtSurgery
    {
        return DB::transaction(function () use ($surgery, $data, $user) {
            $theatreId = $data['theatre_id'] ?? $surgery->theatre_id;
            $theatre = OtTheatre::query()->whereKey($theatreId)->lockForUpdate()->firstOrFail();

            $start = $data['scheduled_start_at'];
            $end = $data['scheduled_end_at'];

            $this->assertNoTheatreConflict($theatre->id, $start, $end, $surgery->id);

            $surgery->update([
                'theatre_id' => $theatre->id,
                'scheduled_date' => $data['scheduled_date'] ?? $start->toDateString(),
                'scheduled_start_at' => $start,
                'scheduled_end_at' => $end,
            ]);

            if ($surgery->status === OtSurgery::STATUS_POSTPONED) {
                app(OtSurgeryLifecycleService::class)->transitionTo($surgery, OtSurgery::STATUS_SCHEDULED);
            }

            OtSurgeryScheduleEvent::create([
                'surgery_id' => $surgery->id,
                'theatre_id' => $theatre->id,
                'change_type' => OtSurgeryScheduleEvent::TYPE_RESCHEDULED,
                'scheduled_date' => $surgery->scheduled_date,
                'scheduled_start_at' => $start,
                'scheduled_end_at' => $end,
                'reason' => $data['reason'] ?? null,
                'changed_by' => $user->id,
                'changed_at' => now(),
            ]);

            event(new SurgeryRescheduled($surgery->fresh()));

            return $surgery->fresh();
        });
    }

    public function postpone(OtSurgery $surgery, string $reason, User $user): OtSurgery
    {
        return DB::transaction(function () use ($surgery, $reason, $user) {
            app(OtSurgeryLifecycleService::class)->transitionTo($surgery, OtSurgery::STATUS_POSTPONED);

            OtSurgeryScheduleEvent::create([
                'surgery_id' => $surgery->id,
                'theatre_id' => $surgery->theatre_id,
                'change_type' => OtSurgeryScheduleEvent::TYPE_POSTPONED,
                'reason' => $reason,
                'changed_by' => $user->id,
                'changed_at' => now(),
            ]);

            event(new SurgeryPostponed($surgery->fresh()));

            return $surgery->fresh();
        });
    }

    public function cancel(OtSurgery $surgery, string $reason, User $user): OtSurgery
    {
        return DB::transaction(function () use ($surgery, $reason, $user) {
            app(OtSurgeryLifecycleService::class)->transitionTo($surgery, OtSurgery::STATUS_CANCELLED);

            $surgery->update(['cancellation_reason' => $reason]);

            OtSurgeryScheduleEvent::create([
                'surgery_id' => $surgery->id,
                'theatre_id' => $surgery->theatre_id,
                'change_type' => OtSurgeryScheduleEvent::TYPE_CANCELLED,
                'reason' => $reason,
                'changed_by' => $user->id,
                'changed_at' => now(),
            ]);

            event(new SurgeryCancelled($surgery->fresh()));

            return $surgery->fresh();
        });
    }

    public function recordDelay(OtSurgery $surgery, string $category, int $minutes, ?string $reason, User $user): OtSurgeryScheduleEvent
    {
        return OtSurgeryScheduleEvent::create([
            'surgery_id' => $surgery->id,
            'theatre_id' => $surgery->theatre_id,
            'change_type' => OtSurgeryScheduleEvent::TYPE_DELAYED,
            'delay_category' => $category,
            'delay_minutes' => $minutes,
            'reason' => $reason,
            'changed_by' => $user->id,
            'changed_at' => now(),
        ]);
    }

    private function assertNoTheatreConflict(int $theatreId, $start, $end, int $excludeSurgeryId): void
    {
        $conflict = OtSurgery::query()
            ->where('theatre_id', $theatreId)
            ->where('id', '!=', $excludeSurgeryId)
            ->whereNotIn('status', [OtSurgery::STATUS_CANCELLED, OtSurgery::STATUS_REJECTED, OtSurgery::STATUS_CLOSED, OtSurgery::STATUS_POSTPONED])
            ->where('scheduled_start_at', '<', $end)
            ->where('scheduled_end_at', '>', $start)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages(['theatre' => 'This theatre is already booked for an overlapping time window.']);
        }
    }

    private function assertNoProviderConflict(int $providerId, $start, $end, int $excludeSurgeryId, string $role): void
    {
        $column = $role === 'surgeon' ? 'primary_surgeon_id' : 'anesthetist_id';

        $conflict = OtSurgery::query()
            ->where($column, $providerId)
            ->where('id', '!=', $excludeSurgeryId)
            ->whereNotIn('status', [OtSurgery::STATUS_CANCELLED, OtSurgery::STATUS_REJECTED, OtSurgery::STATUS_CLOSED, OtSurgery::STATUS_POSTPONED])
            ->where('scheduled_start_at', '<', $end)
            ->where('scheduled_end_at', '>', $start)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([$role => "This {$role} already has an overlapping booking."]);
        }
    }
}
