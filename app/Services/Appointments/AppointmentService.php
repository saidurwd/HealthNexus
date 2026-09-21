<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
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

        $token->appointment->update(['status' => 'in_progress', 'started_at' => now()]);
    }

    public function completeToken(AppointmentToken $token): void
    {
        $token->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $token->appointment->update(['status' => 'completed', 'ended_at' => now()]);
    }
}
