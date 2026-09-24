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

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $rows = NursingAssignment::query()
            ->join('nursing_episodes', 'nursing_episodes.id', '=', 'nursing_assignments.episode_id')
            ->where('nursing_episodes.company_id', $companyId)
            ->whereNull('nursing_assignments.ended_at')
            ->select('nursing_assignments.nurse_id', DB::raw('count(*) as patients'))
            ->groupBy('nursing_assignments.nurse_id')
            ->with('nurse')
            ->get();

        $taskStats = NursingTask::where('company_id', $companyId)
            ->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('admin.nursing.reports.workload', compact('rows', 'taskStats'));
    }

    public function medication()
    {
        Gate::authorize('nursing.reports.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $stats = NursingMedicationAdministration::where('company_id', $companyId)
            ->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('admin.nursing.reports.medication', compact('stats'));
    }

    public function quality()
    {
        Gate::authorize('nursing.reports.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        $riskCounts = NursingRiskAssessment::where('company_id', $companyId)
            ->select('risk_type', 'risk_level', DB::raw('count(*) as total'))->groupBy('risk_type', 'risk_level')->get();
        $escalations = NursingEscalation::where('company_id', $companyId)
            ->selectRaw('count(*) as total, sum(case when resolved_at is null then 1 else 0 end) as open_count')->first();

        return view('admin.nursing.reports.quality', compact('riskCounts', 'escalations'));
    }
}
