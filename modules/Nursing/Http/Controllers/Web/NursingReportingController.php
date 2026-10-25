<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingAssignment;
use App\Models\Nursing\NursingEscalation;
use App\Models\Nursing\NursingMedicationAdministration;
use App\Models\Nursing\NursingRiskAssessment;
use App\Models\Nursing\NursingTask;
use App\Services\TenantContextResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class NursingReportingController extends Controller
{
    public function workload()
    {
        Gate::authorize('nursing.reports.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();

        $rows = NursingAssignment::query()
            ->join('nursing_episodes', 'nursing_episodes.id', '=', 'nursing_assignments.episode_id')
            ->where('nursing_episodes.organization_id', $organizationId)
            ->whereNull('nursing_assignments.ended_at')
            ->select('nursing_assignments.nurse_id', DB::raw('count(*) as patients'))
            ->groupBy('nursing_assignments.nurse_id')
            ->with('nurse')
            ->get();

        $taskStats = NursingTask::where('organization_id', $organizationId)
            ->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('admin.nursing.reports.workload', compact('rows', 'taskStats'));
    }

    public function medication()
    {
        Gate::authorize('nursing.reports.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();

        $stats = NursingMedicationAdministration::where('organization_id', $organizationId)
            ->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('admin.nursing.reports.medication', compact('stats'));
    }

    public function quality()
    {
        Gate::authorize('nursing.reports.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();

        $riskCounts = NursingRiskAssessment::where('organization_id', $organizationId)
            ->select('risk_type', 'risk_level', DB::raw('count(*) as total'))->groupBy('risk_type', 'risk_level')->get();
        $escalations = NursingEscalation::where('organization_id', $organizationId)
            ->selectRaw('count(*) as total, sum(case when resolved_at is null then 1 else 0 end) as open_count')->first();

        return view('admin.nursing.reports.quality', compact('riskCounts', 'escalations'));
    }
}
