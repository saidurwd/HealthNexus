<?php

namespace App\Services\Icu;

use App\Models\Icu\IcuAdmissionRequest;
use App\Models\Icu\IcuCriticalAlert;
use App\Models\Icu\IcuEpisode;
use App\Models\Ipd\IpdBed;
use App\Support\Dashboard\Days;
use Carbon\CarbonImmutable;

/** The figures of the ICU dashboard: beds, who is in, what came and went today, and the alerts nobody has acknowledged (with how long the oldest has waited). Days are Dhaka days. */
class IcuOverview
{
    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $today = Days::today($now);
        [$start, $end] = Days::bounds($today);
        $episodes = fn () => IcuEpisode::forTenant($organizationId, $branchId);
        $alerts = fn () => IcuCriticalAlert::forTenant($organizationId, $branchId);
        $beds = fn () => IpdBed::forTenant($organizationId, $branchId)->where('icu_capable', true);
        $available = (int) $beds()->where('status', IpdBed::STATUS_AVAILABLE)->count();
        $occupied = (int) $beds()->where('status', IpdBed::STATUS_OCCUPIED)->count();
        $oldest = $alerts()->whereNull('acknowledged_at')->min('triggered_at');

        return [
            'current_patients' => $episodes()->whereNull('discharged_at')->count(),
            'pending_requests' => IcuAdmissionRequest::forTenant($organizationId, $branchId)->whereIn('status', [IcuAdmissionRequest::STATUS_REQUESTED, IcuAdmissionRequest::STATUS_PENDING_APPROVAL])->count(),
            'unacknowledged_alerts' => $alerts()->whereNull('acknowledged_at')->count(),
            'oldest_alert_minutes' => $oldest ? max(0, (int) CarbonImmutable::parse($oldest, 'UTC')->diffInMinutes($now)) : null,
            'beds_available' => $available, 'beds_occupied' => $occupied, 'occupancy' => ($available + $occupied) > 0 ? round(100 * $occupied / ($available + $occupied), 1) : null,
            'admissions_today' => $episodes()->whereBetween('admitted_at', [$start, $end])->count(),
            'discharges_today' => $episodes()->whereBetween('discharged_at', [$start, $end])->count(),
            'alerts' => collect(range(0, 13))->map(fn ($i) => ['day' => $today->subDays(13 - $i)->format('d M'), 'raised' => $alerts()->whereBetween('triggered_at', Days::bounds($today->subDays(13 - $i)))->count()])->all(),
        ];
    }
}
