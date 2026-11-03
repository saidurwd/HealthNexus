<?php

namespace Modules\Vital\Http\Controllers\Web;

use App\Models\PatientAddress;
use App\Models\Vital\VrBirthNotification;
use App\Services\TenantContextResolver;
use App\Services\Vital\BirthService;
use Illuminate\Http\Request;

class BirthController extends VitalController
{
    public function __construct(TenantContextResolver $tenant, private readonly BirthService $births)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'vital.birth.view');
        $rows = VrBirthNotification::query()->with('mother:id,first_name,last_name,enterprise_patient_no')->where('organization_id', $this->organizationId())
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))->when($request->query('followup'), fn ($q) => $q->where('status', 'issued')->where('issued_at', '<=', now()->subDays((int) config('vital.registration_followup_days'))))
            ->orderByDesc('born_at')->paginate(25)->withQueryString();

        return view('admin.vital.births.index', ['rows' => $rows, 'can' => ['create' => $request->user()->can('vital.birth.create')]]);
    }

    public function create(Request $request)
    {
        $this->allow($request, 'vital.birth.create');

        return view('admin.vital.births.create');
    }

    public function store(Request $request)
    {
        $this->allow($request, 'vital.birth.create');
        $v = $request->validate([
            'mother_patient_id' => ['required', 'integer'], 'outcome' => ['required', 'in:'.implode(',', config('vital.outcomes'))], 'child_name' => ['nullable', 'string', 'max:150'],
            'sex' => ['required', 'in:'.implode(',', config('vital.sexes'))], 'born_at' => ['required', 'date'], 'place_of_birth' => ['required', 'in:'.implode(',', config('vital.places'))],
            'plurality' => ['nullable', 'integer', 'between:1,6'], 'birth_order' => ['nullable', 'integer', 'between:1,6'], 'weight_g' => ['nullable', 'integer', 'between:200,7000'], 'gestation_weeks' => ['nullable', 'integer', 'between:12,46'],
            'delivery_type' => ['nullable', 'in:'.implode(',', config('vital.delivery_types'))], 'father_name' => ['nullable', 'string', 'max:150'], 'father_nid' => ['nullable', 'string', 'max:30'],
            'informant_name' => ['nullable', 'string', 'max:150'], 'informant_relation' => ['nullable', 'string', 'max:60'], 'attendant_name' => ['nullable', 'string', 'max:150'],
            'division_id' => ['nullable', 'integer', 'exists:geo_divisions,id'], 'district_id' => ['nullable', 'integer', 'exists:geo_districts,id'], 'upazila_id' => ['nullable', 'integer', 'exists:geo_upazilas,id'], 'union_id' => ['nullable', 'integer', 'exists:geo_unions,id'],
            'village_area' => ['nullable', 'string', 'max:120'],
        ]);
        $n = $this->births->draft($this->patient((int) $v['mother_patient_id']), $v + ['attendant_id' => null, 'branch_id' => $this->tenant->getBranchId()], $request->user());

        return redirect()->route('admin.vital.births.show', $n->public_id)->with('success', __('Birth notification written. A second person must check it and issue it.'));
    }

    public function show(Request $request, string $birth)
    {
        $this->allow($request, 'vital.birth.view');
        $n = $this->find(VrBirthNotification::class, $birth);
        $n->load('mother:id,first_name,last_name,enterprise_patient_no', 'child:id,first_name,last_name');
        $place = PatientAddress::query()->newModelInstance(['division_id' => $n->division_id, 'district_id' => $n->district_id, 'upazila_id' => $n->upazila_id, 'union_id' => $n->union_id]);

        return view('admin.vital.births.show', ['n' => $n, 'place' => collect([$place->geoUnion, $place->geoUpazila, $place->geoDistrict, $place->geoDivision])->filter()->map(fn ($g) => $g->displayName())->implode(', '),
            'can' => ['issue' => $request->user()->can('vital.birth.issue') && $n->created_by !== $request->user()->id, 'register' => $request->user()->can('vital.birth.register'), 'cancel' => $request->user()->can('vital.birth.cancel')]]);
    }

    public function issue(Request $request, string $birth)
    {
        $this->allow($request, 'vital.birth.issue');
        $v = $request->validate(['issued_to' => ['required', 'string', 'max:150']]);
        $this->births->issue($this->find(VrBirthNotification::class, $birth), $request->user(), $v['issued_to']);

        return back()->with('success', __('Notification issued.'));
    }

    public function register(Request $request, string $birth)
    {
        $this->allow($request, 'vital.birth.register');
        $v = $request->validate(['registrar_reference' => ['required', 'string', 'max:30']]);
        $this->births->register($this->find(VrBirthNotification::class, $birth), $v['registrar_reference'], $request->user());

        return back()->with('success', __('The registrar\'s number is recorded.'));
    }

    public function cancel(Request $request, string $birth)
    {
        $this->allow($request, 'vital.birth.cancel');
        $v = $request->validate(['cancel_reason' => ['required', 'string', 'max:255']]);
        $this->births->cancel($this->find(VrBirthNotification::class, $birth), $v['cancel_reason'], $request->user());

        return back()->with('success', __('Notification cancelled.'));
    }
}
