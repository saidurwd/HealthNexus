<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\AppointmentSlot;
use App\Models\AppointmentStatusHistory;
use App\Models\AppointmentToken;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function generateAppointmentNo(Company $company): string
    {
        $prefix = strtoupper(substr($company->code, 0, 3));
        $last = Appointment::where('company_id', $company->id)->orderByDesc('id')->first();
        $sequence = $last ? $last->id + 1 : 1;
        return sprintf('%s-APT-%08d', $prefix, $sequence);
    }

    public function generateTokenNo(Company $company, Branch $branch): string
    {
        $prefix = strtoupper(substr($company->code, 0, 3)).strtoupper(substr($branch->code ?? 'BR', 0, 2));
        $last = \App\Models\AppointmentToken::where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->orderByDesc('id')
            ->first();
        $sequence = $last ? $last->id + 1 : 1;
        return sprintf('%s-%08d', $prefix, $sequence);
    }

    public function createAppointment(array $data, User $user): Appointment
    {
        return DB::transaction(function () use ($data, $user): Appointment {
            $company = $user->companies()->findOrFail($data['company_id']);

            $data['company_id'] = $company->id;
            $data['appointment_no'] = $this->generateAppointmentNo($company);
            $data['booked_at'] = now();
            $data['created_by'] = $user->id;

            $appointment = Appointment::create($data);

            $tokenNo = $this->generateTokenNo($company, $company->branches()->firstWhere('id', $data['branch_id']));
            $appointment->token()->create([
                'company_id' => $company->id,
                'branch_id' => $appointment->branch_id,
                'token_number' => $tokenNo,
                'generated_at' => now(),
            ]);

            if ($appointment->slot) {
                $appointment->slot->increment('booked_count');
            }

            return $appointment;
        });
    }

    public function updateAppointment(Appointment $appointment, array $data): Appointment
    {
        $appointment->update($data);
        return $appointment;
    }

    public function generateSlots(DoctorSchedule $schedule, string $date): Collection
    {
        $slots = collect();
        $current = \Carbon\Carbon::parse($date)->setTimeFromTimeString($schedule->start_time->format('H:i'));
        $end = \Carbon\Carbon::parse($date)->setTimeFromTimeString($schedule->end_time->format('H:i'));

        while ($current < $end) {
            $slots->push([
                'company_id' => $schedule->company_id,
                'branch_id' => $schedule->branch_id,
                'doctor_id' => $schedule->doctor_id,
                'schedule_id' => $schedule->id,
                'slot_datetime' => $current,
                'duration_minutes' => $schedule->slot_duration_minutes,
            ]);
            $current->addMinutes($schedule->slot_duration_minutes);
        }

        return $slots;
    }

    public function createSlots(DoctorSchedule $schedule, string $date): Collection
    {
        $slots = $this->generateSlots($schedule, $date);

        $created = collect();
        foreach ($slots as $slot) {
            $created->push(AppointmentSlot::create($slot));
        }

        return $created;
    }

    public function getTodaysQueue($companyId, $branchId = null): Collection
    {
        $query = Appointment::where('company_id', $companyId)
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['scheduled', 'confirmed', 'checked_in', 'in_progress'])
            ->with(['token', 'patient']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('appointment_time')->get();
    }

    public function callNextToken($companyId, $branchId = null, $doctorId = null, $tokenId = null): ?AppointmentToken
    {
        if ($tokenId) {
            return $this->callToken($tokenId);
        }

        $query = AppointmentToken::where('company_id', $companyId)
            ->where('status', 'waiting')
            ->with(['appointment.patient']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($doctorId) {
            $query->whereHas('appointment', fn ($q) => $q->where('doctor_id', $doctorId));
        }

        $token = $query->orderBy('token_number')->first();

        if ($token) {
            $token->update([
                'status' => 'called',
                'called_at' => now(),
                'called_by' => auth()->id(),
            ]);
        }

        return $token;
    }

    public function callToken($tokenId): ?AppointmentToken
    {
        $token = AppointmentToken::where('id', $tokenId)
            ->where('status', 'waiting')
            ->with(['appointment.patient'])
            ->first();

        if ($token) {
            $token->update([
                'status' => 'called',
                'called_at' => now(),
                'called_by' => auth()->id(),
            ]);
        }

        return $token;
    }

    public function startToken(AppointmentToken $token): void
    {
        $token->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $token->appointment->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'actual_datetime' => $token->appointment->actual_datetime ?? now(),
        ]);
    }

    public function completeToken(AppointmentToken $token): void
    {
        $token->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $token->appointment->update([
            'status' => 'completed',
            'ended_at' => now(),
            'completed_at' => now(),
            'actual_datetime' => $token->appointment->actual_datetime ?? now(),
        ]);
    }

    public function complete(Appointment $appointment, ?User $user = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $user) {
            $appointment->update([
                'status' => 'completed',
                'ended_at' => now(),
                'completed_at' => now(),
                'actual_datetime' => $appointment->actual_datetime ?? now(),
            ]);

            $this->recordHistory($appointment, 'completed', $user, null, 'Appointment completed.');

            return $appointment;
        });
    }

    public function checkIn(Appointment $appointment, ?User $user = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $user) {
            $appointment->update([
                'status' => 'checked_in',
                'actual_datetime' => now(),
                'checked_in_at' => now(),
                'checked_in_by' => $user?->id ?? auth()->id(),
            ]);

            if ($appointment->token) {
                $appointment->token->update([
                    'status' => 'checked_in',
                ]);
            }

            $this->recordHistory($appointment, 'checked_in', $user, null, 'Patient checked in.');

            return $appointment;
        });
    }

    public function confirm(Appointment $appointment, ?User $user = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $user) {
            $appointment->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => $user?->id ?? auth()->id(),
            ]);

            $this->recordHistory($appointment, 'confirmed', $user, null, 'Appointment confirmed.');

            return $appointment;
        });
    }

    public function createFollowUp(Appointment $appointment, array $data, User $user): Appointment
    {
        return DB::transaction(function () use ($appointment, $data, $user) {
            $company = $appointment->company;

            $followUpData = [
                'company_id' => $appointment->company_id,
                'branch_id' => $appointment->branch_id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'appointment_no' => $this->generateAppointmentNo($company),
                'appointment_date' => $data['appointment_date'] ?? now()->addDays(7)->toDateString(),
                'appointment_time' => $data['appointment_time'] ?? $appointment->appointment_time,
                'type' => 'followup',
                'source' => 'followup',
                'reason' => $data['reason'] ?? $appointment->reason,
                'notes' => $data['notes'] ?? null,
                'status' => 'scheduled',
                'created_by' => $user->id,
                'actual_datetime' => null,
            ];

            $followUp = Appointment::create($followUpData);

            $tokenNo = $this->generateTokenNo($company, $appointment->branch);
            $followUp->token()->create([
                'company_id' => $company->id,
                'branch_id' => $followUp->branch_id,
                'token_number' => $tokenNo,
                'generated_at' => now(),
            ]);

            return $followUp;
        });
    }

    public function recordHistory(Appointment $appointment, string $newStatus, ?User $user = null, ?string $reason = null, ?string $notes = null): AppointmentStatusHistory
    {
        return $appointment->statusHistory()->create([
            'old_status' => $appointment->status,
            'new_status' => $newStatus,
            'changed_by' => $user?->id ?? auth()->id(),
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }

    public function reschedule(Appointment $appointment, array $data, ?User $user = null): Appointment
    {
        $oldStatus = $appointment->status;

        $appointment->update([
            'appointment_date' => $data['appointment_date'] ?? $appointment->appointment_date,
            'appointment_time' => $data['appointment_time'] ?? $appointment->appointment_time,
            'doctor_id' => $data['doctor_id'] ?? $appointment->doctor_id,
            'slot_id' => $data['slot_id'] ?? $appointment->slot_id,
        ]);

        $this->recordHistory($appointment, $appointment->status, $user, $data['reason'] ?? null, 'Appointment rescheduled.');

        return $appointment;
    }

    public function cancel(Appointment $appointment, ?string $reason = null, ?User $user = null): Appointment
    {
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $user?->id ?? auth()->id(),
            'cancellation_reason' => $reason,
        ]);

        $this->recordHistory($appointment, 'cancelled', $user, $reason, 'Appointment cancelled.');

        return $appointment;
    }

    public function markNoShow(Appointment $appointment, ?string $reason = null, ?User $user = null): Appointment
    {
        $appointment->update([
            'status' => 'no_show',
            'no_show_at' => now(),
        ]);

        $this->recordHistory($appointment, 'no_show', $user, $reason, 'Appointment marked as no-show.');

        return $appointment;
    }

    public function addNote(Appointment $appointment, string $note, ?User $user = null): AppointmentNote
    {
        return $appointment->notes()->create([
            'company_id' => $appointment->company_id,
            'note' => $note,
            'created_by' => $user?->id ?? auth()->id(),
        ]);
    }
}
