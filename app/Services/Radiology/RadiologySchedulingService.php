<?php

namespace App\Services\Radiology;

use App\Models\Provider;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologyOrderItem;
use App\Models\User;
use App\Services\Appointments\AppointmentBookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reuses the existing Appointment engine (Phase 6 plan decision #1) rather than a parallel
 * scheduling framework. Appointment.doctor_id remains a mandatory FK to `users` even after the
 * generic Provider abstraction, so the assigned technologist/radiologist Provider must have a
 * linked user account (Provider::user_id) to be schedulable — validated explicitly rather than
 * failing with an opaque DB constraint error.
 *
 * Modality double-booking is prevented with a lightweight gap-lock check on
 * radiology_examinations.modality_id (not a full parallel ModalitySchedule/ModalitySlot
 * pre-generation system — a deliberate scope reduction, see the Phase 6 plan).
 */
class RadiologySchedulingService
{
    public function __construct(private readonly AppointmentBookingService $booking) {}

    public function schedule(
        RadiologyOrderItem $item,
        RadiologyModality $modality,
        Provider $technologist,
        Carbon $dateTime,
        User $user,
        int $durationMinutes = 30,
    ): RadiologyExamination {
        return DB::transaction(function () use ($item, $modality, $technologist, $dateTime, $user, $durationMinutes) {
            if (! $technologist->user_id) {
                throw ValidationException::withMessages([
                    'technologist' => 'The assigned technologist/radiologist must have a linked user account to be scheduled.',
                ]);
            }

            $this->assertModalityAvailable($modality->id, $dateTime, $durationMinutes);

            $order = $item->radiologyOrder;

            $appointment = $this->booking->book([
                'company_id' => $order->company_id,
                'branch_id' => $order->branch_id,
                'patient_id' => $order->patient_id,
                'doctor_id' => $technologist->user_id,
                'provider_id' => $technologist->id,
                'room_id' => $modality->room_id,
                'appointment_date' => $dateTime->toDateString(),
                'appointment_time' => $dateTime->format('H:i'),
                'type' => 'radiology',
                'source' => 'radiology',
                'reason' => $item->procedure?->name ?? $item->requested_procedure_name,
            ], $user);

            $examination = RadiologyExamination::create([
                'company_id' => $order->company_id,
                'branch_id' => $order->branch_id,
                'order_item_id' => $item->id,
                'modality_id' => $modality->id,
                'patient_id' => $order->patient_id,
                'encounter_id' => $order->encounter_id,
                'appointment_id' => $appointment->id,
                'scheduled_at' => $dateTime,
                'status' => 'scheduled',
                'technologist_id' => $technologist->id,
            ]);

            $item->update(['status' => 'scheduled']);

            app(RadiologyOrderLifecycleService::class)->transitionTo($order, 'scheduled');

            return $examination;
        });
    }

    /**
     * Gap-lock: a locking SELECT over the modality/time window acquires an InnoDB gap lock even
     * when it matches zero rows (default REPEATABLE READ), blocking a concurrent transaction
     * from scheduling a colliding examination on the same modality until this one commits —
     * mirrors AppointmentBookingService::assertNoConflict()'s technique.
     */
    private function assertModalityAvailable(int $modalityId, Carbon $dateTime, int $durationMinutes): void
    {
        $windowStart = $dateTime->copy()->subMinutes($durationMinutes - 1);
        $windowEnd = $dateTime->copy()->addMinutes($durationMinutes - 1);

        $conflict = RadiologyExamination::query()
            ->where('modality_id', $modalityId)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->lockForUpdate()
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages(['scheduled_at' => 'This modality is already booked for the selected time.']);
        }
    }
}
