<?php

namespace Modules\Facility\Http\Controllers\Web;

use App\Models\Facility\FacAmbulance;
use App\Models\Facility\FacAmbulanceTrip;
use App\Models\Patient;
use App\Services\Facility\AmbulanceService;
use App\Services\Facility\FacilityPolicy;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** The ambulance fleet and its trips. */
class AmbulanceController extends FacilityController
{
    public function __construct(TenantContextResolver $tenant, private readonly AmbulanceService $ambulances, private readonly FacilityPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'facility.ambulance.view', 'facility.ambulance.request');
        $org = $this->organizationId();
        $trips = FacAmbulanceTrip::query()->with('ambulance', 'patient')->where('organization_id', $org)->orderByRaw("case status when 'requested' then 0 when 'dispatched' then 1 when 'arrived' then 1 else 2 end")->orderByDesc('requested_at')->paginate(25);

        return view('admin.facility.ambulances', ['fleet' => FacAmbulance::query()->where('organization_id', $org)->orderBy('registration_no')->get(), 'trips' => $trips, 'types' => $this->policy->list('ambulance.types'), 'kinds' => $this->policy->list('ambulance.trip_kinds'),
            'response' => $this->ambulances->averageResponseMinutes($org, today()->subDays(29)->toDateString(), today()->toDateString()), 'can' => ['manage' => $request->user()->can('facility.ambulance.manage'), 'request' => $request->user()->can('facility.ambulance.request')]]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'facility.ambulance.manage');
        $d = $request->validate(['registration_no' => ['required', 'string', 'max:30'], 'type' => ['required', 'string', 'max:10'], 'odometer_km' => ['nullable', 'integer', 'min:0']]);
        $this->ambulances->add($this->organizationId(), $d['registration_no'], $d['type'], null, (int) ($d['odometer_km'] ?? 0));

        return back()->with('success', __('The vehicle was added.'));
    }

    public function maintenance(Request $request, string $ambulance)
    {
        $this->allow($request, 'facility.ambulance.manage');
        $a = $this->find(FacAmbulance::class, $ambulance);
        $this->ambulances->setMaintenance($a, $a->status !== 'maintenance');

        return back()->with('success', __('Saved.'));
    }

    public function fuel(Request $request, string $ambulance)
    {
        $this->allow($request, 'facility.ambulance.manage');
        $d = $request->validate(['litres' => ['required', 'numeric', 'min:0.1'], 'cost' => ['required', 'numeric', 'min:0'], 'odometer_km' => ['nullable', 'integer', 'min:0']]);
        $this->ambulances->fuel($this->find(FacAmbulance::class, $ambulance), (float) $d['litres'], (float) $d['cost'], isset($d['odometer_km']) ? (int) $d['odometer_km'] : null);

        return back()->with('success', __('Saved.'));
    }

    public function request(Request $request)
    {
        $this->allow($request, 'facility.ambulance.request', 'facility.ambulance.manage');
        $d = $request->validate(['kind' => ['required', 'string', 'max:10'], 'from_place' => ['required', 'string', 'max:150'], 'to_place' => ['required', 'string', 'max:150'], 'patient_no' => ['nullable', 'string', 'max:40']]);
        $patient = ! empty($d['patient_no']) ? Patient::query()->where('organization_id', $this->organizationId())->where('enterprise_patient_no', trim($d['patient_no']))->first() : null;
        if (! empty($d['patient_no']) && ! $patient) {
            throw ValidationException::withMessages(['patient_no' => __('No patient has that number.')]);
        }
        $this->ambulances->request($this->organizationId(), $d + ['patient_id' => $patient?->id, 'branch_id' => $this->branchId()], $request->user());

        return back()->with('success', __('The trip was requested.'));
    }

    public function dispatch(Request $request, string $trip)
    {
        $this->allow($request, 'facility.ambulance.manage');
        $d = $request->validate(['ambulance_id' => ['required', 'integer'], 'driver_name' => ['required', 'string', 'max:100']]);
        $a = FacAmbulance::query()->where('organization_id', $this->organizationId())->findOrFail($d['ambulance_id']);
        $this->ambulances->dispatch($this->find(FacAmbulanceTrip::class, $trip), $a, $d['driver_name']);

        return back()->with('success', __('The ambulance was sent.'));
    }

    public function arrive(Request $request, string $trip)
    {
        $this->allow($request, 'facility.ambulance.manage');
        $this->ambulances->arrive($this->find(FacAmbulanceTrip::class, $trip));

        return back()->with('success', __('Saved.'));
    }

    public function complete(Request $request, string $trip)
    {
        $this->allow($request, 'facility.ambulance.manage');
        $d = $request->validate(['odometer_end' => ['required', 'integer', 'min:0']]);
        $this->ambulances->complete($this->find(FacAmbulanceTrip::class, $trip), (int) $d['odometer_end']);

        return back()->with('success', __('The trip is finished.'));
    }

    public function cancel(Request $request, string $trip)
    {
        $this->allow($request, 'facility.ambulance.manage', 'facility.ambulance.request');
        $d = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        $this->ambulances->cancel($this->find(FacAmbulanceTrip::class, $trip), $d['reason']);

        return back()->with('success', __('The trip was cancelled.'));
    }
}
