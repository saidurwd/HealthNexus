<?php

namespace Modules\Clinical\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class ClinicalReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $base = Encounter::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('encounter_date', [$from, $to]);

        $stats = [
            'total' => (clone $base)->count(),
            'by_status' => (clone $base)->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status'),
            'by_type' => (clone $base)->selectRaw('encounter_type, count(*) as aggregate')->groupBy('encounter_type')->pluck('aggregate', 'encounter_type'),
            'locked' => (clone $base)->whereNotNull('locked_at')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
        ];

        return view('admin.reports.clinical', compact('stats', 'from', 'to'));
    }

    public function dailyOpd(Request $request)
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $date = $request->input('date', now()->toDateString());

        $encounters = Encounter::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('encounter_date', $date)
            ->with(['patient', 'provider', 'encounterType'])
            ->orderBy('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.reports.clinical-daily-opd', compact('encounters', 'date'));
    }

    public function providerWorkload(Request $request)
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $workload = Encounter::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('encounter_date', [$from, $to])
            ->whereNotNull('provider_id')
            ->with('provider')
            ->selectRaw('provider_id, count(*) as total, sum(case when status = "completed" then 1 else 0 end) as completed')
            ->groupBy('provider_id')
            ->orderByDesc('total')
            ->get();

        return view('admin.reports.clinical-provider-workload', compact('workload', 'from', 'to'));
    }

    public function diagnosisStatistics(Request $request)
    {
        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $diagnoses = Diagnosis::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('recorded_at', [$from, $to.' 23:59:59'])
            ->selectRaw('description, coding_system, count(*) as aggregate')
            ->groupBy('description', 'coding_system')
            ->orderByDesc('aggregate')
            ->limit(50)
            ->get();

        return view('admin.reports.clinical-diagnosis-statistics', compact('diagnoses', 'from', 'to'));
    }
}
