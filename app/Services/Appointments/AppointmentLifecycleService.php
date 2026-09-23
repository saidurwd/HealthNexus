<?php

namespace App\Services\Appointments;

use App\Events\Appointments\AppointmentCancelled;
use App\Events\Appointments\AppointmentCheckedIn;
use App\Events\Appointments\AppointmentCompleted;
use App\Events\Appointments\AppointmentConfirmed;
use App\Events\Appointments\AppointmentNoShow as AppointmentNoShowEvent;
use App\Events\Appointments\AppointmentRescheduled;
use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\AppointmentStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Confirm/check-in/complete/reschedule/cancel/no-show, all going through AppointmentStateMachine
 * instead of writing `status` directly, and fixing a bug found in the gap audit: the previous
 * recordHistory() always logged old_status === new_status because every caller updated the
 * appointment's status *before* calling it — by the time it read $appointment->status, the
 * in-memory model already held the new value. Every history row here captures the prior status
 * first.
 */
class AppointmentLifecycleService
{
    public function __construct(private AppointmentStateMachine $stateMachine) {}

    public function confirm(Appointment $appointment, ?User $user = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $user) {
            $this->stateMachine->assertTransition($appointment, 'confirmed');
            $oldStatus = $appointment->status;

            $appointment->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => $user?->id ?? auth()->id(),
            ]);

            $this->recordHistory($appointment, $oldStatus, 'confirmed', $user, null, 'Appointment confirmed.');
            AppointmentConfirmed::dispatch($appointment);

            return $appointment;
        });
    }

    public function checkIn(Appointment $appointment, ?User $user = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $user) {
            $this->stateMachine->assertTransition($appointment, 'checked_in');
            $oldStatus = $appointment->status;

            $appointment->update([
                'status' => 'checked_in',
                'actual_datetime' => now(),
                'checked_in_at' => now(),
                'checked_in_by' => $user?->id ?? auth()->id(),
            ]);

            if ($appointment->token) {
                $appointment->token->update(['status' => 'checked_in']);
            }

            $this->recordHistory($appointment, $oldStatus, 'checked_in', $user, null, 'Patient checked in.');
            AppointmentCheckedIn::dispatch($appointment);

            return $appointment;
        });
    }

    public function startConsultation(Appointment $appointment, ?User $user = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $user) {
            $this->stateMachine->assertTransition($appointment, 'in_progress');
            $oldStatus = $appointment->status;

            $appointment->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'actual_datetime' => $appointment->actual_datetime ?? now(),
            ]);

            if ($appointment->token) {
                $appointment->token->update(['status' => 'in_progress', 'started_at' => now()]);
            }

            $this->recordHistory($appointment, $oldStatus, 'in_progress', $user, null, 'Consultation started.');

            return $appointment;
        });
    }

    public function complete(Appointment $appointment, ?User $user = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $user) {
            $this->stateMachine->assertTransition($appointment, 'completed');
            $oldStatus = $appointment->status;

            $appointment->update([
                'status' => 'completed',
                'ended_at' => now(),
                'completed_at' => now(),
                'actual_datetime' => $appointment->actual_datetime ?? now(),
            ]);

            if ($appointment->token) {
                $appointment->token->update(['status' => 'completed', 'completed_at' => now()]);
            }

            $this->recordHistory($appointment, $oldStatus, 'completed', $user, null, 'Appointment completed.');
            AppointmentCompleted::dispatch($appointment);

            return $appointment;
        });
    }

    public function cancel(Appointment $appointment, string $reason, ?User $user = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason, $user) {
            $this->stateMachine->assertTransition($appointment, 'cancelled');
            $oldStatus = $appointment->status;

            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $user?->id ?? auth()->id(),
                'cancellation_reason' => $reason,
            ]);

            if ($appointment->token) {
                $appointment->token->update(['status' => 'cancelled']);
            }

            if ($appointment->slot) {
                $appointment->slot->decrement('booked_count');
                if ($appointment->slot->fresh()->status === 'booked') {
                    $appointment->slot->update(['status' => 'available']);
                }
            }

            $this->recordHistory($appointment, $oldStatus, 'cancelled', $user, $reason, 'Appointment cancelled.');
            AppointmentCancelled::dispatch($appointment);

            return $appointment;
        });
    }

    public function markNoShow(Appointment $appointment, ?string $reason = null, ?User $user = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason, $user) {
            $this->stateMachine->assertTransition($appointment, 'no_show');
            $oldStatus = $appointment->status;

            $appointment->update([
                'status' => 'no_show',
                'no_show_at' => now(),
            ]);

            $this->recordHistory($appointment, $oldStatus, 'no_show', $user, $reason, 'Appointment marked as no-show.');
            AppointmentNoShowEvent::dispatch($appointment);

            return $appointment;
        });
    }

    /**
     * Preserves full history instead of silently overwriting the original date/time — records
     * the old values on the status-history row before mutating the appointment.
     */
    public function reschedule(Appointment $appointment, array $data, ?User $user = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $data, $user) {
            $this->stateMachine->assertReschedulable($appointment);

            $oldValues = $appointment->only(['appointment_date', 'appointment_time', 'doctor_id', 'provider_id', 'slot_id']);

            if ($appointment->slot) {
                $appointment->slot->decrement('booked_count');
                if ($appointment->slot->fresh()->status === 'booked') {
                    $appointment->slot->update(['status' => 'available']);
                }
            }

            $appointment->update([
                'appointment_date' => $data['appointment_date'] ?? $appointment->appointment_date,
                'appointment_time' => $data['appointment_time'] ?? $appointment->appointment_time,
                'doctor_id' => $data['doctor_id'] ?? $appointment->doctor_id,
                'provider_id' => $data['provider_id'] ?? $appointment->provider_id,
                'slot_id' => $data['slot_id'] ?? null,
            ]);

            if ($appointment->slot) {
                $appointment->slot->increment('booked_count');
            }

            $appointment->statusHistory()->create([
                'old_status' => $appointment->status,
                'new_status' => $appointment->status,
                'changed_by' => $user?->id ?? auth()->id(),
                'reason' => $data['reason'] ?? null,
                'notes' => sprintf(
                    'Rescheduled from %s %s to %s %s.',
                    $oldValues['appointment_date'], $oldValues['appointment_time'],
                    $appointment->appointment_date->toDateString(), $appointment->appointment_time->format('H:i'),
                ),
            ]);

            AppointmentRescheduled::dispatch($appointment, $oldValues);

            return $appointment;
        });
    }

    public function addNote(Appointment $appointment, string $note, ?User $user = null): AppointmentNote
    {
        return $appointment->notes()->create([
            'company_id' => $appointment->company_id,
            'note' => $note,
            'created_by' => $user?->id ?? auth()->id(),
        ]);
    }

    private function recordHistory(
        Appointment $appointment,
        string $oldStatus,
        string $newStatus,
        ?User $user = null,
        ?string $reason = null,
        ?string $notes = null,
    ): AppointmentStatusHistory {
        return $appointment->statusHistory()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $user?->id ?? auth()->id(),
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }
}
