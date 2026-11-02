<?php

namespace App\Services\Ipd;

use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdAdmissionRequest;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedMovement;
use App\Support\Dashboard\Days;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The figures of the IPD (Indoor) dashboard: where the beds are, what came in and went out today (set against the same weekday over four weeks, with a 14-day trend), and what is waiting. Days are Dhaka days. Counts only.
 * The keys the old page used are kept, so nothing that read them breaks.
 */
class IpdOverview
{
    private const INPATIENT = ['admitted', 'active', 'transfer_requested', 'transferred', 'discharge_planned', 'discharge_pending'];

    /** @return array<string,mixed> */
    public function summary(int $organizationId, ?int $branchId, ?CarbonImmutable $now = null): array
    {
        $today = Days::today($now);
        [$start, $end] = Days::bounds($today);
        $adm = fn () => IpdAdmission::query()->forTenant($organizationId, $branchId);
        $usual = fn (callable $count) => round(collect(range(1, 4))->map(fn ($w) => (float) $count($today->subWeeks($w)))->avg(), 1);
        $beds = IpdBed::query()->forTenant($organizationId, $branchId)->where('ipd_beds.is_active', true)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $beds->sum();
        $occupied = (int) ($beds[IpdBed::STATUS_OCCUPIED] ?? 0);
        $operational = $total - (int) ($beds[IpdBed::STATUS_OUT_OF_SERVICE] ?? 0) - (int) ($beds[IpdBed::STATUS_MAINTENANCE] ?? 0);

        $byWard = DB::table('ipd_beds as b')->join('ipd_rooms as r', 'r.id', '=', 'b.room_id')->join('ipd_wards as w', 'w.id', '=', 'r.ward_id')->where('b.organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('b.branch_id', $branchId))
            ->where('b.is_active', true)->whereNull('b.deleted_at')->selectRaw("w.name as ward, sum(case when b.status in ('out_of_service', 'maintenance') then 0 else 1 end) as beds, sum(case when b.status = 'occupied' then 1 else 0 end) as occupied")->groupBy('w.id', 'w.name')->orderBy('w.name')->get()
            ->map(fn ($r) => ['ward' => $r->ward, 'beds' => (int) $r->beds, 'occupied' => (int) $r->occupied, 'rate' => $r->beds > 0 ? round(100 * $r->occupied / $r->beds, 1) : 0.0])->all();

        $admissions = fn (CarbonImmutable $d) => $adm()->whereBetween('admitted_at', Days::bounds($d))->count();

        return [
            'date' => $today->toDateString(),
            'admissions_today' => $admissions($today),
            'admissions_usual' => $usual($admissions),
            'discharges_today' => $adm()->whereBetween('actual_discharge_date', [$start, $end])->count(),
            'discharges_usual' => $usual(fn ($d) => $adm()->whereBetween('actual_discharge_date', Days::bounds($d))->count()),
            'transfers_today' => IpdBedMovement::query()->forTenant($organizationId, $branchId)->where('movement_type', 'transfer')->whereBetween('moved_at', [$start, $end])->count(),
            'current_inpatients' => $adm()->whereIn('status', self::INPATIENT)->count(),
            'pending_admission_requests' => IpdAdmissionRequest::query()->forTenant($organizationId, $branchId)->whereIn('status', [IpdAdmissionRequest::STATUS_REQUESTED, IpdAdmissionRequest::STATUS_PENDING_APPROVAL])->count(),
            'pending_transfers' => IpdBedMovement::query()->forTenant($organizationId, $branchId)->where('movement_type', 'transfer')->whereIn('status', ['requested', 'approved'])->count(),
            'expected_discharges_today' => $adm()->where('status', 'active')->whereDate('expected_discharge_date', $today->toDateString())->count(),
            'delayed_discharges' => $adm()->where('status', 'active')->whereNotNull('expected_discharge_date')->whereDate('expected_discharge_date', '<', $today->toDateString())->count(),
            'total_beds' => $total, 'available_beds' => (int) ($beds[IpdBed::STATUS_AVAILABLE] ?? 0), 'occupied_beds' => $occupied, 'reserved_beds' => (int) ($beds[IpdBed::STATUS_RESERVED] ?? 0),
            'cleaning_beds' => (int) ($beds[IpdBed::STATUS_CLEANING] ?? 0), 'maintenance_beds' => (int) ($beds[IpdBed::STATUS_MAINTENANCE] ?? 0), 'isolation_beds' => (int) ($beds[IpdBed::STATUS_ISOLATION] ?? 0),
            'operational_beds' => $operational, 'occupancy_rate' => $operational > 0 ? round(($occupied / $operational) * 100, 1) : 0.0,
            'by_ward' => $byWard,
            'flow' => collect(range(0, 13))->map(function ($i) use ($today, $adm) {
                $d = $today->subDays(13 - $i);

                return ['day' => $d->format('d M'), 'admitted' => $adm()->whereBetween('admitted_at', Days::bounds($d))->count(), 'discharged' => $adm()->whereBetween('actual_discharge_date', Days::bounds($d))->count()];
            })->all(),
        ];
    }
}
