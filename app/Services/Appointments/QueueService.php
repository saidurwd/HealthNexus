<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentToken;
use App\Models\Provider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Waiting-queue operations over appointment_tokens (the token/queue concept is deliberately
 * consolidated onto one table rather than a separate appointment_queue table — see the migration
 * comment on add_priority_fields_to_appointment_tokens_table). Call-next honors queue priority
 * (Emergency > Priority > VIP > Regular > Follow-up) ahead of plain arrival order.
 */
class QueueService
{
    public function todaysQueue(int $companyId, ?int $branchId = null): Collection
    {
        return Appointment::where('company_id', $companyId)
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['scheduled', 'confirmed', 'checked_in', 'in_progress'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['token', 'patient', 'provider'])
            ->orderBy('appointment_time')
            ->get();
    }

    public function callNext(int $companyId, ?int $branchId = null, ?int $providerId = null, ?int $tokenId = null): ?AppointmentToken
    {
        if ($tokenId) {
            return $this->call($tokenId);
        }

        return DB::transaction(function () use ($companyId, $branchId, $providerId) {
            // FIELD() is MySQL-only; a CASE expression over PRIORITY_ORDER is portable (also
            // runs correctly against the sqlite connection this suite's tests use).
            $cases = collect(AppointmentToken::PRIORITY_ORDER)
                ->map(fn ($priority, $rank) => "WHEN '{$priority}' THEN {$rank}")
                ->implode(' ');
            $priorityRank = "CASE priority {$cases} ELSE ".count(AppointmentToken::PRIORITY_ORDER).' END';

            $token = AppointmentToken::where('company_id', $companyId)
                ->where('status', 'waiting')
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($providerId, fn ($q) => $q->whereHas('appointment', fn ($q2) => $q2->where('provider_id', $providerId)))
                ->orderByRaw($priorityRank)
                ->orderBy('token_number')
                ->lockForUpdate()
                ->with('appointment.patient')
                ->first();

            if (! $token) {
                return null;
            }

            $token->update(['status' => 'called', 'called_at' => now(), 'called_by' => auth()->id()]);

            return $token;
        });
    }

    public function call(int $tokenId): ?AppointmentToken
    {
        $token = AppointmentToken::where('id', $tokenId)->where('status', 'waiting')->with('appointment.patient')->first();

        $token?->update(['status' => 'called', 'called_at' => now(), 'called_by' => auth()->id()]);

        return $token;
    }

    public function recall(AppointmentToken $token): AppointmentToken
    {
        if ($token->status !== 'called') {
            throw ValidationException::withMessages(['status' => 'Only a currently-called token can be recalled.']);
        }

        $token->update(['called_at' => now(), 'called_by' => auth()->id()]);

        return $token;
    }

    public function skip(AppointmentToken $token): AppointmentToken
    {
        $token->update(['status' => 'skipped', 'skipped_at' => now()]);

        return $token;
    }

    public function transfer(AppointmentToken $token, Provider $toProvider): AppointmentToken
    {
        $token->update(['status' => 'waiting', 'transferred_to_provider_id' => $toProvider->id]);
        $token->appointment?->update(['provider_id' => $toProvider->id]);

        return $token;
    }

    public function cancel(AppointmentToken $token): AppointmentToken
    {
        $token->update(['status' => 'cancelled']);

        return $token;
    }
}
