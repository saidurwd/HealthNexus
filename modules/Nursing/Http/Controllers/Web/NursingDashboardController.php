<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingAssignment;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingMedicationAdministration;
use App\Models\Nursing\NursingTask;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class NursingDashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', NursingEpisode::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();
        $user = $request->user();

        $myEpisodeIds = NursingAssignment::query()
            ->where('nurse_id', $user->id)
            ->whereNull('ended_at')
            ->pluck('episode_id');

        $metrics = [
            'my_patients' => $myEpisodeIds->unique()->count(),
            'ward_patients' => NursingEpisode::forTenant($companyId, $branchId)->where('status', NursingEpisode::STATUS_ACTIVE)->count(),
            'pending_assessments' => \App\Models\Nursing\NursingAssessment::forTenant($companyId, $branchId)->where('status', 'draft')->count(),
            'due_medications' => NursingMedicationAdministration::forTenant($companyId, $branchId)->whereIn('status', ['scheduled', 'due'])->count(),
            'overdue_medications' => NursingMedicationAdministration::forTenant($companyId, $branchId)->where('status', 'due')->where('scheduled_at', '<', now())->count(),
            'pending_tasks' => NursingTask::forTenant($companyId, $branchId)->where('status', NursingTask::STATUS_PENDING)->count(),
            'overdue_tasks' => NursingTask::forTenant($companyId, $branchId)->where('status', NursingTask::STATUS_OVERDUE)->count(),
            'critical_alerts' => \App\Models\Nursing\NursingAlert::forTenant($companyId, $branchId)->whereNull('resolved_at')->whereIn('severity', ['high', 'critical'])->count(),
            'pending_handover' => \App\Models\Nursing\NursingHandover::forTenant($companyId, $branchId)->where('status', 'pending_acknowledgement')->count(),
        ];

        $myPatients = NursingEpisode::query()
            ->whereIn('id', $myEpisodeIds)
            ->with(['patient', 'admission.currentAllocation.bed.room.ward'])
            ->get();

        return view('admin.nursing.dashboard.index', compact('metrics', 'myPatients'));
    }
}
