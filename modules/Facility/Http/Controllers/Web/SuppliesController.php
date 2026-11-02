<?php

namespace Modules\Facility\Http\Controllers\Web;

use App\Models\Facility\FacLinenMovement;
use App\Models\Facility\FacLinenType;
use App\Models\Facility\FacWasteManifest;
use App\Models\Facility\FacWasteRecord;
use App\Services\Facility\FacilityPolicy;
use App\Services\Facility\LinenService;
use App\Services\Facility\WasteService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Linen and biomedical waste. */
class SuppliesController extends FacilityController
{
    public function __construct(TenantContextResolver $tenant, private readonly LinenService $linen, private readonly WasteService $waste, private readonly FacilityPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function linen(Request $request)
    {
        $this->allow($request, 'facility.linen.view');
        $types = FacLinenType::query()->where('organization_id', $this->organizationId())->where('active', true)->orderBy('code')->get();

        return view('admin.facility.linen', ['types' => $types, 'balances' => $types->mapWithKeys(fn ($t) => [$t->id => $this->linen->balance($t)]), 'movements' => $this->policy->list('linen.movements'),
            'recent' => FacLinenMovement::query()->with('type')->where('organization_id', $this->organizationId())->orderByDesc('id')->limit(25)->get(), 'canManage' => $request->user()->can('facility.linen.manage')]);
    }

    public function linenType(Request $request)
    {
        $this->allow($request, 'facility.linen.manage');
        $d = $request->validate(['code' => ['required', 'string', 'max:20'], 'name' => ['required', 'string', 'max:100'], 'par_level' => ['nullable', 'integer', 'min:0']]);
        $this->linen->type($this->organizationId(), $d['code'], $d['name'], isset($d['par_level']) ? (int) $d['par_level'] : null);

        return back()->with('success', __('Saved.'));
    }

    public function linenMove(Request $request)
    {
        $this->allow($request, 'facility.linen.manage');
        $d = $request->validate(['linen_type_id' => ['required', 'integer'], 'movement' => ['required', 'string', 'max:16'], 'quantity' => ['required', 'integer', 'min:1', 'max:100000'], 'location' => ['nullable', 'string', 'max:80'], 'moved_on' => ['nullable', 'date'], 'note' => ['nullable', 'string', 'max:200']]);
        $type = FacLinenType::query()->where('organization_id', $this->organizationId())->findOrFail($d['linen_type_id']);
        $this->linen->move($type, $d['movement'], (int) $d['quantity'], $request->user(), $d['location'] ?? null, $d['moved_on'] ?? null, $d['note'] ?? null, $this->branchId());

        return back()->with('success', __('Recorded.'));
    }

    public function waste(Request $request)
    {
        $this->allow($request, 'facility.waste.view');
        $org = $this->organizationId();
        $from = today()->subDays(29)->toDateString();

        return view('admin.facility.waste', ['categories' => $this->policy->list('waste.categories'), 'totals' => $this->waste->totals($org, $from, today()->toDateString()), 'waiting' => (float) FacWasteRecord::query()->where('organization_id', $org)->whereNull('manifest_id')->sum('weight_kg'),
            'recent' => FacWasteRecord::query()->where('organization_id', $org)->orderByDesc('recorded_on')->orderByDesc('id')->limit(20)->get(), 'manifests' => FacWasteManifest::query()->where('organization_id', $org)->orderByDesc('id')->limit(15)->get(), 'canManage' => $request->user()->can('facility.waste.manage')]);
    }

    public function wasteRecord(Request $request)
    {
        $this->allow($request, 'facility.waste.manage', 'facility.housekeeping.work');
        $d = $request->validate(['category' => ['required', 'string', 'max:14'], 'location' => ['required', 'string', 'max:80'], 'weight_kg' => ['required', 'numeric', 'min:0.01', 'max:5000'], 'recorded_on' => ['nullable', 'date']]);
        $this->waste->record($this->organizationId(), $d['category'], $d['location'], (float) $d['weight_kg'], $request->user(), $d['recorded_on'] ?? null, $this->branchId());

        return back()->with('success', __('Recorded.'));
    }

    public function manifest(Request $request)
    {
        $this->allow($request, 'facility.waste.manage');
        $d = $request->validate(['contractor' => ['required', 'string', 'max:150'], 'vehicle_no' => ['nullable', 'string', 'max:30'], 'collected_on' => ['nullable', 'date']]);
        $this->waste->manifest($this->organizationId(), $d['contractor'], $d['vehicle_no'] ?? null, $d['collected_on'] ?? null, $this->branchId());

        return back()->with('success', __('The manifest was made.'));
    }

    public function handOver(Request $request, string $manifest)
    {
        $this->allow($request, 'facility.waste.manage');
        $this->waste->handOver($this->find(FacWasteManifest::class, $manifest), $request->user());

        return back()->with('success', __('Handed over.'));
    }

    public function confirm(Request $request, string $manifest)
    {
        $this->allow($request, 'facility.waste.manage');
        $d = $request->validate(['certificate_no' => ['required', 'string', 'max:60'], 'disposed_on' => ['nullable', 'date']]);
        $this->waste->confirm($this->find(FacWasteManifest::class, $manifest), $d['certificate_no'], $d['disposed_on'] ?? null);

        return back()->with('success', __('Disposal confirmed.'));
    }
}
