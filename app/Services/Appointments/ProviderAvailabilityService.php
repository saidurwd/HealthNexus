<?php

namespace App\Services\Appointments;

use App\Models\AppointmentSlot;
use App\Models\HospitalHoliday;
use App\Models\Provider;
use App\Models\ProviderUnavailability;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Nets out a provider's actual bookable slots: schedule minus already-booked capacity minus
 * leave/unavailability minus holiday minus blocked time. A provider's availability is a
 * calculated result, never just "Monday = available" (per spec §30).
 */
class ProviderAvailabilityService
{
    public function availableSlotsOn(Provider $provider, Carbon $date, bool $withOverbooking = false): Collection
    {
        if ($this->isHoliday($provider, $date)) {
            return collect();
        }

        return AppointmentSlot::query()
            ->whereHas('schedule', fn ($q) => $q->where('provider_id', $provider->id))
            ->whereDate('slot_datetime', $date)
            ->where('status', 'available')
            ->with('schedule')
            ->orderBy('slot_datetime')
            ->get()
            ->filter(fn (AppointmentSlot $slot) => $slot->isAvailable($withOverbooking)
                && ! $this->isUnavailable($provider, $slot->slot_datetime, $slot->duration_minutes))
            ->values();
    }

    public function isProviderBookable(Provider $provider, Carbon $dateTime, int $durationMinutes): bool
    {
        if (! $provider->isActive()) {
            return false;
        }

        if ($this->isHoliday($provider, $dateTime)) {
            return false;
        }

        return ! $this->isUnavailable($provider, $dateTime, $durationMinutes);
    }

    private function isHoliday(Provider $provider, Carbon $date): bool
    {
        return HospitalHoliday::where('company_id', $provider->company_id)
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $provider->branch_id))
            ->where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', $provider->department_id))
            ->get()
            ->contains(fn (HospitalHoliday $holiday) => $holiday->appliesTo($date));
    }

    private function isUnavailable(Provider $provider, Carbon $start, int $durationMinutes): bool
    {
        $end = $start->copy()->addMinutes($durationMinutes);

        return ProviderUnavailability::where('provider_id', $provider->id)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();
    }
}
