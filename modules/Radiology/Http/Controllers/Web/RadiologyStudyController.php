<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\Radiology\SyncPacsStudyMetadata;
use App\Models\Radiology\RadiologyStudy;
use App\Services\Radiology\RadiologyStudyService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RadiologyStudyController extends Controller
{
    public function __construct(private readonly RadiologyStudyService $studies) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', RadiologyStudy::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $studies = RadiologyStudy::query()
            ->forTenant($companyId, $branchId)
            ->with(['patient', 'examination.orderItem.procedure'])
            ->when($request->filled('search'), fn ($q) => $q->where('accession_number', 'like', '%'.$request->input('search').'%'))
            ->latest('study_date')
            ->paginate(20);

        return view('admin.radiology.studies.index', compact('studies'));
    }

    public function show(RadiologyStudy $study)
    {
        $this->authorize('view', $study);

        $study->load(['patient', 'examination.orderItem.procedure', 'series.instances', 'pacsServer']);

        return view('admin.radiology.studies.show', compact('study'));
    }

    public function viewer(Request $request, RadiologyStudy $study)
    {
        $this->authorize('view', $study);

        try {
            $url = $this->studies->viewerUrl($study, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->away($url);
    }

    public function sync(Request $request, RadiologyStudy $study)
    {
        $this->authorize('view', $study);

        SyncPacsStudyMetadata::dispatch($study->examination_id);

        return redirect()->route('admin.radiology.studies.show', $study)->with('success', 'Checking PACS for images — this may take a moment.');
    }
}
