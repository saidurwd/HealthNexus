<?php

namespace Modules\Facility\Http\Controllers\Web;

use App\Models\Facility\FacMortuaryCase;
use App\Models\Facility\FacMortuaryUnit;
use App\Models\User;
use App\Services\Facility\FacilityPolicy;
use App\Services\Facility\MortuaryService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** The mortuary: bodies waiting to be received, those in store, and the two-person release. */
class MortuaryController extends FacilityController
{
    public function __construct(TenantContextResolver $tenant, private readonly MortuaryService $mortuary, private readonly FacilityPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'facility.mortuary.view');
        $org = $this->organizationId();
        $status = $request->query('status', 'open');
        $cases = FacMortuaryCase::query()->with('patient', 'unit')->where('organization_id', $org)->when($status === 'open', fn ($q) => $q->whereIn('status', ['pending', 'stored', 'release_pending']))->when($status === 'released', fn ($q) => $q->where('status', 'released'))->orderByDesc('died_at')->paginate(25)->withQueryString();

        return view('admin.facility.mortuary', ['cases' => $cases, 'status' => $status, 'units' => FacMortuaryUnit::query()->where('organization_id', $org)->where('active', true)->orderBy('code')->get(), 'occupancy' => $this->mortuary->occupancy($org), 'idTypes' => $this->policy->list('mortuary.id_types'),
            'staff' => $this->staff(), 'service' => $this->mortuary, 'me' => $request->user()->id, 'can' => ['manage' => $request->user()->can('facility.mortuary.manage'), 'release' => $request->user()->can('facility.mortuary.release')]]);
    }

    public function unit(Request $request)
    {
        $this->allow($request, 'facility.mortuary.manage');
        $d = $request->validate(['code' => ['required', 'string', 'max:20'], 'name' => ['required', 'string', 'max:80'], 'capacity' => ['required', 'integer', 'min:1', 'max:100']]);
        $this->mortuary->unit($this->organizationId(), $d['code'], $d['name'], (int) $d['capacity']);

        return back()->with('success', __('Saved.'));
    }

    public function findDeaths(Request $request)
    {
        $this->allow($request, 'facility.mortuary.manage');

        return back()->with('success', __(':n new death(s) to receive.', ['n' => $this->mortuary->sweep($this->organizationId())]));
    }

    public function receive(Request $request, string $case)
    {
        $this->allow($request, 'facility.mortuary.manage');
        $d = $request->validate(['verifier_id' => ['required', 'integer'], 'tag_no' => ['required', 'string', 'max:30'], 'unit_id' => ['required', 'integer'], 'police_case' => ['nullable', 'boolean']]);
        $verifier = User::query()->whereHas('organizations', fn ($q) => $q->where('organizations.id', $this->organizationId()))->findOrFail($d['verifier_id']);
        $unit = FacMortuaryUnit::query()->where('organization_id', $this->organizationId())->findOrFail($d['unit_id']);
        $this->mortuary->receive($this->caseFor($case), $request->user(), $verifier, $d['tag_no'], $unit, (bool) ($d['police_case'] ?? false));

        return back()->with('success', __('The body was received and stored.'));
    }

    public function police(Request $request, string $case)
    {
        $this->allow($request, 'facility.mortuary.manage');
        $this->mortuary->clearPolice($this->caseFor($case), $request->user());

        return back()->with('success', __('The police have cleared the case.'));
    }

    public function requestRelease(Request $request, string $case)
    {
        $this->allow($request, 'facility.mortuary.manage');
        $d = $request->validate(['released_to' => ['required', 'string', 'max:150'], 'relation' => ['required', 'string', 'max:60'], 'id_type' => ['required', 'string', 'max:20'], 'id_number' => ['required', 'string', 'max:60']]);
        $this->mortuary->requestRelease($this->caseFor($case), $request->user(), $d['released_to'], $d['relation'], $d['id_type'], $d['id_number']);

        return back()->with('success', __('Waiting for a second person to authorise the release.'));
    }

    public function authorise(Request $request, string $case)
    {
        $this->allow($request, 'facility.mortuary.release');
        $this->mortuary->authoriseRelease($this->caseFor($case), $request->user());

        return back()->with('success', __('The release is authorised.'));
    }

    public function cancelRelease(Request $request, string $case)
    {
        $this->allow($request, 'facility.mortuary.manage');
        $this->mortuary->cancelRelease($this->caseFor($case));

        return back()->with('success', __('The release was cancelled.'));
    }

    private function caseFor(string $publicId): FacMortuaryCase
    {
        return FacMortuaryCase::query()->where('public_id', $publicId)->where('organization_id', $this->organizationId())->firstOrFail();
    }
}
