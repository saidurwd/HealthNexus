<?php

namespace Modules\Facility\Http\Controllers\Web;

use App\Models\Facility\FacAsset;
use App\Models\Facility\FacBatchItem;
use App\Models\Facility\FacInstrumentSet;
use App\Models\Facility\FacSterilisationBatch;
use App\Services\Facility\CssdService;
use App\Services\Facility\FacilityPolicy;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Sterile services: sets, batches, release by a second person, packs to operations, recall and trace. */
class CssdController extends FacilityController
{
    public function __construct(TenantContextResolver $tenant, private readonly CssdService $cssd, private readonly FacilityPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'facility.cssd.view');
        $org = $this->organizationId();

        return view('admin.facility.cssd.index', ['batches' => FacSterilisationBatch::query()->withCount('items')->where('organization_id', $org)->orderByDesc('id')->paginate(20), 'sets' => FacInstrumentSet::query()->where('organization_id', $org)->where('active', true)->orderBy('code')->get(),
            'machines' => FacAsset::query()->where('organization_id', $org)->where('category', 'biomedical')->where('status', '!=', 'retired')->orderBy('tag')->get(['id', 'tag', 'name']), 'methods' => $this->policy->list('cssd.methods'), 'canManage' => $request->user()->can('facility.cssd.manage')]);
    }

    public function storeSet(Request $request)
    {
        $this->allow($request, 'facility.cssd.manage');
        $d = $request->validate(['code' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:150'], 'instrument_count' => ['nullable', 'integer', 'min:0', 'max:2000']]);
        $this->cssd->set($this->organizationId(), $d['code'], $d['name'], (int) ($d['instrument_count'] ?? 0));

        return back()->with('success', __('Saved.'));
    }

    public function load(Request $request)
    {
        $this->allow($request, 'facility.cssd.manage');
        $d = $request->validate(['method' => ['required', 'string', 'max:8'], 'asset_id' => ['nullable', 'integer'], 'sets' => ['required', 'array', 'min:1'], 'sets.*' => ['integer', 'min:0'], 'qty' => ['nullable', 'array'], 'qty.*' => ['nullable', 'integer', 'min:0', 'max:50']]);
        $ids = [];
        foreach ($d['sets'] as $n => $setId) {
            for ($i = 0; $i < (int) ($d['qty'][$n] ?? 1); $i++) {
                $ids[] = (int) $setId;
            }
        }
        $machine = ! empty($d['asset_id']) ? FacAsset::query()->where('organization_id', $this->organizationId())->find($d['asset_id']) : null;
        $batch = $this->cssd->load($this->organizationId(), $d['method'], $ids, $request->user(), $machine, $this->branchId());

        return redirect()->route('admin.facility.cssd.show', $batch->public_id)->with('success', __('The batch was loaded.'));
    }

    public function show(Request $request, string $batch)
    {
        $this->allow($request, 'facility.cssd.view');
        $b = $this->find(FacSterilisationBatch::class, $batch);
        $b->load('items.set', 'asset');

        return view('admin.facility.cssd.show', ['b' => $b, 'staff' => $this->staff(), 'me' => $request->user()->id, 'trace' => $this->cssd->trace($b),
            'surgeries' => DB::table('ot_surgeries')->where('organization_id', $this->organizationId())->orderByDesc('id')->limit(40)->get(['id', 'surgery_number']), 'can' => ['manage' => $request->user()->can('facility.cssd.manage'), 'release' => $request->user()->can('facility.cssd.release')]]);
    }

    public function start(Request $request, string $batch)
    {
        $this->allow($request, 'facility.cssd.manage');
        $this->cssd->start($this->find(FacSterilisationBatch::class, $batch));

        return back()->with('success', __('The cycle has started.'));
    }

    public function finish(Request $request, string $batch)
    {
        $this->allow($request, 'facility.cssd.manage');
        $d = $request->validate(['indicator' => ['required', 'in:pass,fail'], 'temperature' => ['nullable', 'numeric', 'min:0', 'max:400'], 'pressure' => ['nullable', 'numeric', 'min:0', 'max:5000'], 'minutes' => ['nullable', 'integer', 'min:0', 'max:1440']]);
        $this->cssd->finish($this->find(FacSterilisationBatch::class, $batch), $d['indicator'], isset($d['temperature']) ? (float) $d['temperature'] : null, isset($d['pressure']) ? (float) $d['pressure'] : null, isset($d['minutes']) ? (int) $d['minutes'] : null);

        return back()->with('success', __('Saved.'));
    }

    public function release(Request $request, string $batch)
    {
        $this->allow($request, 'facility.cssd.release');
        $this->cssd->release($this->find(FacSterilisationBatch::class, $batch), $request->user());

        return back()->with('success', __('The batch is released.'));
    }

    public function recall(Request $request, string $batch)
    {
        $this->allow($request, 'facility.cssd.release');
        $d = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $used = $this->cssd->recall($this->find(FacSterilisationBatch::class, $batch), $request->user(), $d['reason']);

        return back()->with('warning', __('The batch was recalled. :n pack(s) had already been used.', ['n' => count($used)]));
    }

    public function issue(Request $request, int $item)
    {
        $this->allow($request, 'facility.cssd.manage');
        $d = $request->validate(['surgery_id' => ['required', 'integer']]);
        $pack = FacBatchItem::query()->whereHas('batch', fn ($q) => $q->where('organization_id', $this->organizationId()))->findOrFail($item);
        $this->cssd->issue($pack, (int) $d['surgery_id'], $request->user());

        return back()->with('success', __('The pack was given to the operation.'));
    }

    public function trace(Request $request)
    {
        $this->allow($request, 'facility.cssd.view');
        $d = $request->validate(['surgery' => ['nullable', 'string', 'max:40']]);
        $surgery = ! empty($d['surgery']) ? DB::table('ot_surgeries')->where('organization_id', $this->organizationId())->where('surgery_number', trim($d['surgery']))->first(['id', 'surgery_number']) : null;

        return view('admin.facility.cssd.trace', ['query' => $d['surgery'] ?? '', 'surgery' => $surgery, 'packs' => $surgery ? $this->cssd->packsUsedIn((int) $surgery->id) : []]);
    }
}
