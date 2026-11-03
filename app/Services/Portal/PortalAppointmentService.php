<?php

namespace App\Services\Portal;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AppointmentToken;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\Portal\PortalNotification;
use App\Models\Provider;
use App\Services\Appointments\AppointmentBookingService;
use App\Services\Appointments\AppointmentLifecycleService;
use App\Services\Appointments\ProviderAvailabilityService;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Booking from the portal uses the very same booking service, slot lock and state machine as the front desk (S03). Rescheduling is "book the new slot, release the old one" in
 * one transaction, so a failed new booking never loses the old appointment.
 */
class PortalAppointmentService
{
    public function __construct(
        private readonly AppointmentBookingService $booking,
        private readonly AppointmentLifecycleService $lifecycle,
        private readonly ProviderAvailabilityService $availability,
        private readonly PortalSystemUser $system,
        private readonly PortalPolicy $policy,
        private readonly AuditLogger $audit,
    ) {}

    /** @return Collection<int, Provider> */
    public function providers(int $organizationId, ?int $specialtyId = null): Collection
    {
        return Provider::query()->with('specialty:id,name')->where('organization_id', $organizationId)->where('status', 'active')->whereIn('provider_type', ['doctor', 'consultant', 'physician', 'specialist', 'dentist'])
            ->when($specialtyId, fn ($q) => $q->where('specialty_id', $specialtyId))->orderBy('name')->get();
    }

    /** @return Collection<int, array{token:string,starts_at:Carbon,minutes:int}> */
    public function slots(Provider $provider, Carbon $date): Collection
    {
        if ($date->lt(now()->startOfDay()) || $date->gt(now()->addDays($this->policy->int('appointments.horizon_days', 60)))) {
            return collect();
        }
        $lead = now()->addHours($this->policy->int('appointments.min_lead_hours', 2));

        return $this->availability->availableSlotsOn($provider, $date)->filter(fn (AppointmentSlot $s) => $s->slot_datetime->gte($lead))
            ->map(fn (AppointmentSlot $s) => ['token' => Crypt::encryptString((string) $s->id), 'starts_at' => $s->slot_datetime, 'minutes' => (int) $s->duration_minutes])->values();
    }

    public function book(PatientPortalAccount $account, Patient $patient, string $slotToken, ?string $reason): Appointment
    {
        $slot = $this->slotFrom($slotToken);
        $this->assertCanBook($patient, $slot);
        $user = $this->system->for($patient->organization_id);

        $appointment = $this->booking->book([
            'patient_id' => $patient->id, 'organization_id' => $patient->organization_id, 'branch_id' => $slot->branch_id, 'slot_id' => $slot->id, 'type' => 'scheduled', 'source' => 'patient_portal',
            'reason' => $reason ? mb_substr($reason, 0, 500) : null, 'priority' => 'routine', 'created_by' => $user->id,
        ], $user);
        $this->audit->log('CREATE', Appointment::class, $appointment->id, null, ['via' => 'portal', 'account_id' => $account->id]);
        $this->tell($patient, __('Appointment booked'), $appointment->appointment_date->format('d M Y').' '.$appointment->appointment_time->format('H:i'));

        return $appointment;
    }

    public function cancel(PatientPortalAccount $account, Appointment $appointment, ?string $reason): Appointment
    {
        $this->assertCanChange($appointment);
        $done = $this->lifecycle->cancel($appointment, $reason ?: 'Cancelled by the patient online', $this->system->for($appointment->organization_id));
        $this->audit->log('UPDATE', Appointment::class, $appointment->id, null, ['via' => 'portal', 'account_id' => $account->id, 'status' => 'cancelled']);

        return $done;
    }

    public function reschedule(PatientPortalAccount $account, Appointment $appointment, string $slotToken): Appointment
    {
        $this->assertCanChange($appointment);

        return DB::transaction(function () use ($account, $appointment, $slotToken) {
            $patient = Patient::query()->findOrFail($appointment->patient_id);
            $slot = $this->slotFrom($slotToken);
            $user = $this->system->for($appointment->organization_id);
            $new = $this->booking->book([
                'patient_id' => $patient->id, 'organization_id' => $patient->organization_id, 'branch_id' => $slot->branch_id, 'slot_id' => $slot->id, 'type' => 'scheduled', 'source' => 'patient_portal',
                'reason' => $appointment->reason, 'priority' => 'routine', 'created_by' => $user->id, 'previous_appointment_id' => $appointment->id,
            ], $user);
            $this->lifecycle->cancel($appointment, 'Moved to another time by the patient online', $user);
            $this->audit->log('UPDATE', Appointment::class, $appointment->id, null, ['via' => 'portal', 'account_id' => $account->id, 'rescheduled_to' => $new->id]);

            return $new;
        });
    }

    /** @return Collection<int, Appointment> */
    public function upcoming(array $patientIds): Collection
    {
        return Appointment::query()->with(['provider:id,name', 'token', 'branch:id,name'])->whereIn('patient_id', $patientIds)->whereIn('status', ['scheduled', 'confirmed', 'checked_in', 'in_progress'])
            ->whereDate('appointment_date', '>=', now()->toDateString())->orderBy('appointment_date')->orderBy('appointment_time')->get();
    }

    /** @return Collection<int, Appointment> */
    public function past(array $patientIds, int $limit = 20): Collection
    {
        return Appointment::query()->with(['provider:id,name'])->whereIn('patient_id', $patientIds)->where(fn ($q) => $q->whereIn('status', ['completed', 'cancelled', 'no_show'])->orWhereDate('appointment_date', '<', now()->toDateString()))
            ->orderByDesc('appointment_date')->limit($limit)->get();
    }

    /** The digital queue: where I am today. Only the number of people ahead is shown, never who they are. */
    public function queue(Appointment $appointment): ?array
    {
        $token = AppointmentToken::query()->where('appointment_id', $appointment->id)->first();
        if ($token === null || ! $appointment->appointment_date->isToday() || ! in_array($token->status, ['waiting', 'called'], true)) {
            return null;
        }
        $ahead = AppointmentToken::query()->where('branch_id', $token->branch_id)->where('status', 'waiting')->where('token_number', '<', $token->token_number)
            ->whereHas('appointment', fn ($q) => $q->where('provider_id', $appointment->provider_id)->whereDate('appointment_date', now()->toDateString()))->count();

        return ['token' => (int) $token->token_number, 'status' => $token->status, 'ahead' => $ahead, 'called' => $token->status === 'called'];
    }

    private function slotFrom(string $token): AppointmentSlot
    {
        try {
            $slot = AppointmentSlot::query()->with('schedule')->find((int) Crypt::decryptString($token));
        } catch (DecryptException) {
            $slot = null;
        }
        if ($slot === null) {
            throw ValidationException::withMessages(['slot' => __('That time is no longer available. Please choose another.')]);
        }

        return $slot;
    }

    private function assertCanBook(Patient $patient, AppointmentSlot $slot): void
    {
        if ($slot->organization_id !== $patient->organization_id || $slot->slot_datetime->lt(now()->addHours($this->policy->int('appointments.min_lead_hours', 2)))) {
            throw ValidationException::withMessages(['slot' => __('That time is no longer available. Please choose another.')]);
        }
        $open = Appointment::query()->where('patient_id', $patient->id)->whereIn('status', ['scheduled', 'confirmed'])->whereDate('appointment_date', '>=', now()->toDateString());
        if ((clone $open)->count() >= $this->policy->int('appointments.max_open', 6)) {
            throw ValidationException::withMessages(['slot' => __('You already have several appointments waiting. Please cancel one or call us.')]);
        }
        if ((clone $open)->where('provider_id', DoctorSchedule::query()->whereKey($slot->schedule_id)->value('provider_id'))->whereDate('appointment_date', $slot->slot_datetime->toDateString())->exists()) {
            throw ValidationException::withMessages(['slot' => __('You already have an appointment with this doctor on that day.')]);
        }
    }

    private function assertCanChange(Appointment $appointment): void
    {
        if (! in_array($appointment->status, ['scheduled', 'confirmed'], true)) {
            throw new PortalException(__('This appointment can no longer be changed online.'), 409);
        }
        $starts = Carbon::parse($appointment->appointment_date->toDateString().' '.$appointment->appointment_time->format('H:i'));
        if ($starts->lt(now()->addHours($this->policy->int('appointments.change_cutoff_hours', 4)))) {
            throw new PortalException(__('It is too close to the appointment to change it online. Please call us.'), 409);
        }
    }

    private function tell(Patient $patient, string $title, string $body): void
    {
        PortalNotification::create(['organization_id' => $patient->organization_id, 'patient_id' => $patient->id, 'kind' => 'appointment', 'title' => $title, 'body' => $body, 'link' => '/portal/appointments']);
    }
}
