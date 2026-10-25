<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Radiology\RadiologyExamination;
use App\Models\Radiology\RadiologyModality;
use App\Services\Radiology\RadiologyWorklistService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RadiologyWorklistController extends Controller
{
    public function __construct(private readonly RadiologyWorklistService $worklist) {}

    public function index(Request $request)
    {
        Gate::authorize('radiology.worklist.view');

        $organizationId = app(TenantContextResolver::class)->getOrganizationId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $examinations = $this->worklist->query($organizationId, $branchId, $request->only(['modality_id', 'priority', 'status', 'date', 'assigned_radiologist_id', 'unassigned', 'accession_number']));
        $modalities = RadiologyModality::query()->forTenant($organizationId, $branchId)->where('is_active', true)->orderBy('name')->get();
        $radiologists = Provider::query()->where('organization_id', $organizationId)->where('provider_type', 'radiologist')->where('status', 'active')->get();

        return view('admin.radiology.worklist.index', compact('examinations', 'modalities', 'radiologists'));
    }

    public function assign(Request $request, RadiologyExamination $examination)
    {
        Gate::authorize('radiology.worklist.assign');

        $validated = $request->validate(['radiologist_id' => ['required', 'integer', 'exists:providers,id']]);

        $examination->update([
            'assigned_radiologist_id' => $validated['radiologist_id'],
            'assigned_at' => now(),
            'assigned_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.radiology.worklist.index')->with('success', 'Study assigned.');
    }
}
