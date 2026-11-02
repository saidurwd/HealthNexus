<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingAssignment;
use App\Models\Nursing\NursingEpisode;
use App\Services\Dashboard\DashboardCache;
use App\Services\Nursing\NursingOverview;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class NursingDashboardController extends Controller
{
    public function __construct(private readonly NursingOverview $overview, private readonly DashboardCache $cache, private readonly TenantContextResolver $tenant) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', NursingEpisode::class);
        $org = (int) $this->tenant->getOrganizationId();
        $branch = $this->tenant->getBranchId() ? (int) $this->tenant->getBranchId() : null;
        $user = $request->user();
        // Some counts are the nurse's own, so the figures are worked out and kept for this nurse.
        $r = $this->cache->remember($user, 'nursing:u'.$user->id, $org, $branch, null, fn () => $this->overview->summary($org, $branch, (int) $user->id), fresh: $request->boolean('fresh'));
        // The nurse's own patients are named: a worklist, not part of the cached figures.
        $myEpisodeIds = NursingAssignment::query()->where('nurse_id', $user->id)->whereNull('ended_at')->pluck('episode_id');
        $myPatients = NursingEpisode::query()->whereIn('id', $myEpisodeIds)->with(['patient', 'admission.currentAllocation.bed.room.ward'])->get();

        return view('admin.nursing.dashboard.index', ['metrics' => $r['data'], 'asOf' => $r['as_of'], 'myPatients' => $myPatients]);
    }
}
