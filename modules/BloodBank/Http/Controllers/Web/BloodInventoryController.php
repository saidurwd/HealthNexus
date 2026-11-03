<?php

namespace Modules\BloodBank\Http\Controllers\Web;

use App\Models\Blood\BloodStorageLocation;
use App\Models\Blood\BloodTemperatureLog;
use App\Models\Blood\BloodUnit;
use App\Services\Blood\BloodInventoryService;
use App\Services\Blood\BloodIssueService;
use App\Services\Blood\BloodPolicy;
use App\Services\Blood\BloodTraceabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BloodInventoryController extends BloodController
{
    public function index(Request $request, BloodPolicy $policy)
    {
        $this->allow($request, 'inventory.view');
        $units = BloodUnit::query()->with('location:id,name')->where('organization_id', $this->organizationId())
            ->when($request->query('component'), fn ($q, $v) => $q->where('component', $v))->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('abo'), fn ($q, $v) => $q->where('abo', $v))->when($request->query('rh'), fn ($q, $v) => $q->where('rh', $v))
            ->when($request->query('q'), fn ($q, $v) => $q->where('unit_number', 'like', "%$v%"))
            ->orderBy('expires_at')->paginate(30)->withQueryString();

        return view('admin.blood.inventory.index', ['units' => $units, 'components' => array_keys($policy->get('components')), 'statuses' => ['quarantined', 'available', 'reserved', 'issued', 'transfused', 'blocked', 'expired', 'discarded', 'processed'], 'filters' => $request->query()]);
    }

    public function show(Request $request, BloodUnit $unit, BloodTraceabilityService $trace, BloodPolicy $policy)
    {
        $this->scoped($request, $unit, 'unit.view');
        $link = $trace->donorOfUnit($unit);

        return view('admin.blood.inventory.show', [
            'unit' => $unit->load('location'), 'events' => $unit->events()->get(), 'donor' => $link['donor']?->load('patient'), 'donation' => $link['donation'],
            'recipients' => $request->user()->can('bloodbank.lookback.view') ? $trace->recipientsOfUnit($unit) : collect(), 'discardReasons' => $policy->get('discard_reasons'),
        ]);
    }

    public function discard(Request $request, BloodUnit $unit, BloodIssueService $issues): RedirectResponse
    {
        $this->scoped($request, $unit, 'unit.discard');
        $validated = $request->validate(['reason' => ['required', 'string'], 'note' => ['nullable', 'string', 'max:300']]);
        $issues->discard($unit, $validated['reason'], $request->user(), $validated['note'] ?? null);

        return back()->with('success', __('Unit discarded.'));
    }

    public function storage(Request $request)
    {
        $this->allow($request, 'inventory.view');
        $locations = BloodStorageLocation::query()->where('organization_id', $this->organizationId())->where('is_active', true)->orderBy('id')->get();

        return view('admin.blood.inventory.storage', [
            'locations' => $locations->map(fn ($l) => ['location' => $l, 'logs' => BloodTemperatureLog::where('location_id', $l->id)->latest('recorded_at')->limit(8)->get(), 'units' => BloodUnit::where('location_id', $l->id)->whereIn('status', ['available', 'reserved', 'quarantined', 'blocked'])->count()]),
        ]);
    }

    public function temperature(Request $request, BloodStorageLocation $location, BloodInventoryService $inventory): RedirectResponse
    {
        $this->scoped($request, $location, 'temperature.record');
        $validated = $request->validate(['celsius' => ['required', 'numeric', 'between:-90,60']]);
        $log = $inventory->recordTemperature($location, (float) $validated['celsius'], $request->user());

        return back()->with($log->is_excursion ? 'warning' : 'success', $log->is_excursion ? __('Outside the safe range — the bags in this place are blocked and the team has been alerted.') : __('Reading recorded.'));
    }
}
