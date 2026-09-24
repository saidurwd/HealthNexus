<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedMovement;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IpdReportingController extends Controller
{
    public function occupancy(Request $request)
    {
        Gate::authorize('ipd.reports.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $byWard = IpdBed::query()
            ->forTenant($companyId, $branchId)
            ->where('ipd_beds.is_active', true)
            ->join('ipd_rooms', 'ipd_rooms.id', '=', 'ipd_beds.room_id')
            ->join('ipd_wards', 'ipd_wards.id', '=', 'ipd_rooms.ward_id')
            ->selectRaw('ipd_wards.name as ward_name, ipd_beds.status, count(*) as total')
            ->groupBy('ipd_wards.name', 'ipd_beds.status')
            ->get()
            ->groupBy('ward_name');

        return view('admin.ipd.reports.occupancy', compact('byWard'));
    }

    public function admissions(Request $request)
    {
        Gate::authorize('ipd.reports.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $admissions = IpdAdmission::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'admissionType', 'department', 'attendingProvider'])
            ->whereDate('admitted_at', '>=', $from)
            ->whereDate('admitted_at', '<=', $to)
            ->latest('admitted_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.ipd.reports.admissions', compact('admissions', 'from', 'to'));
    }

    public function discharges(Request $request)
    {
        Gate::authorize('ipd.reports.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $discharges = IpdAdmission::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'dischargeDisposition'])
            ->whereNotNull('actual_discharge_date')
            ->whereDate('actual_discharge_date', '>=', $from)
            ->whereDate('actual_discharge_date', '<=', $to)
            ->latest('actual_discharge_date')
            ->paginate(30)
            ->withQueryString();

        return view('admin.ipd.reports.discharges', compact('discharges', 'from', 'to'));
    }

    public function transfers(Request $request)
    {
        Gate::authorize('ipd.reports.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $transfers = IpdBedMovement::query()
            ->forTenant($companyId, $branchId)
            ->where('movement_type', IpdBedMovement::TYPE_TRANSFER)
            ->with(['patient', 'fromBed.room.ward', 'toBed.room.ward'])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.ipd.reports.transfers', compact('transfers'));
    }
}
