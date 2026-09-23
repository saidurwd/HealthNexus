<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyReport;
use App\Models\Radiology\RadiologyReportTemplate;
use App\Services\AuditLogger;
use App\Services\Radiology\RadiologyReportService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Modules\Radiology\Http\Requests\Web\AmendRadiologyReportRequest;
use Modules\Radiology\Http\Requests\Web\StoreRadiologyReportRequest;

class RadiologyReportController extends Controller
{
    public function __construct(
        private readonly RadiologyReportService $reports,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyReport::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $reports = RadiologyReport::query()
            ->forTenant($companyId, $branchId)
            ->where('is_current', true)
            ->with('examination.orderItem.radiologyOrder.patient')
            ->when($request->filled('search'), fn ($q) => $q->where('report_number', 'like', '%'.$request->input('search').'%'))
            ->latest('created_at')
            ->paginate(20);

        return view('admin.radiology.reports.index', compact('reports'));
    }

    public function create(RadiologyExamination $examination)
    {
        $this->authorize('create', RadiologyReport::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $templates = RadiologyReportTemplate::query()->forTenant($companyId)->where('is_active', true)->get();

        return view('admin.radiology.reports.create', compact('examination', 'templates'));
    }

    public function store(StoreRadiologyReportRequest $request, RadiologyExamination $examination)
    {
        $this->authorize('create', RadiologyReport::class);

        $radiologist = Provider::where('user_id', $request->user()->id)->firstOrFail();
        $report = $this->reports->createDraft($examination, $radiologist, $request->validated());

        $this->auditLogger->log('CREATE', RadiologyReport::class, $report->id, null, $report->toArray(), $request);

        return redirect()->route('admin.radiology.reports.show', $report)->with('success', 'Report draft created.');
    }

    public function show(RadiologyReport $report)
    {
        $this->authorize('view', $report);

        $report->load(['examination.orderItem.radiologyOrder.patient', 'examination.orderItem.procedure', 'examination.modality', 'radiologist', 'criticalFindings']);

        return view('admin.radiology.reports.show', compact('report'));
    }

    public function update(Request $request, RadiologyReport $report)
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'clinical_indication' => ['nullable', 'string'],
            'technique' => ['nullable', 'string'],
            'findings' => ['nullable', 'string'],
            'impression' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
        ]);

        $oldValues = $report->toArray();
        $this->reports->update($report, $validated);

        $this->auditLogger->log('UPDATE', RadiologyReport::class, $report->id, $oldValues, $report->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.reports.show', $report)->with('success', 'Report updated.');
    }

    public function submit(Request $request, RadiologyReport $report)
    {
        $this->authorize('submit', $report);

        $oldValues = $report->toArray();
        $this->reports->submit($report);

        $this->auditLogger->log('SUBMIT', RadiologyReport::class, $report->id, $oldValues, $report->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.reports.show', $report)->with('success', 'Report submitted for approval.');
    }

    public function approve(Request $request, RadiologyReport $report)
    {
        $this->authorize('approve', $report);

        $approver = Provider::where('user_id', $request->user()->id)->firstOrFail();
        $oldValues = $report->toArray();

        $this->reports->approve($report, $approver);

        $this->auditLogger->log('APPROVE', RadiologyReport::class, $report->id, $oldValues, $report->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.reports.show', $report)->with('success', 'Report approved and finalized.');
    }

    public function amend(AmendRadiologyReportRequest $request, RadiologyReport $report)
    {
        $this->authorize('amend', $report);

        $amended = $this->reports->amend($report, $request->validated(), $request->validated('reason'), $request->user());

        $this->auditLogger->log('AMEND', RadiologyReport::class, $amended->id, $report->toArray(), $amended->toArray(), $request);

        return redirect()->route('admin.radiology.reports.show', $amended)->with('success', 'Report amended — a new version has been recorded.');
    }
}
