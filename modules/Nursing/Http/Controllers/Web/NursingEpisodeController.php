<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingEpisode;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class NursingEpisodeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', NursingEpisode::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $episodes = NursingEpisode::forTenant($companyId, $branchId)
            ->with(['patient', 'primaryNurse', 'admission.currentAllocation.bed.room.ward'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('start_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.nursing.episodes.index', compact('episodes'));
    }

    public function show(NursingEpisode $episode)
    {
        $this->authorize('view', $episode);

        $episode->load([
            'patient', 'admission.currentAllocation.bed.room.ward', 'primaryNurse',
            'assignments' => fn ($q) => $q->whereNull('ended_at')->with('nurse'),
            'encounter',
        ]);

        return view('admin.nursing.episodes.show', compact('episode'));
    }
}
