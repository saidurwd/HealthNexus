<?php

namespace Modules\Vital\Http\Controllers\Web;

use App\Models\Patient;
use App\Models\Vital\VrDeathCertification;
use App\Services\TenantContextResolver;
use App\Services\Vital\DeathService;
use Illuminate\Http\Request;

class DeathController extends VitalController
{
    public function __construct(TenantContextResolver $tenant, private readonly DeathService $deaths)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'vital.death.view');
        $org = $this->organizationId();
        $rows = VrDeathCertification::query()->with('patient:id,first_name,last_name,enterprise_patient_no')->where('organization_id', $org)->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('died_at')->paginate(25)->withQueryString();
        $awaiting = $this->deaths->awaiting($org);
        $names = Patient::query()->where('organization_id', $org)->whereIn('id', $awaiting->pluck('patient_id'))->get(['id', 'first_name', 'last_name', 'enterprise_patient_no'])->keyBy('id');

        return view('admin.vital.deaths.index', ['rows' => $rows, 'awaiting' => $awaiting->take(20), 'awaitingTotal' => $awaiting->count(), 'names' => $names, 'can' => ['create' => $request->user()->can('vital.death.create')]]);
    }

    public function create(Request $request)
    {
        $this->allow($request, 'vital.death.create');
        $patient = $request->filled('patient_id') ? Patient::query()->where('organization_id', $this->organizationId())->find((int) $request->query('patient_id')) : null;

        return view('admin.vital.deaths.create', ['patient' => $patient, 'diedAt' => $patient?->deceased_at]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'vital.death.create');
        $v = $request->validate([
            'patient_id' => ['required', 'integer'], 'died_at' => ['required', 'date'], 'place_of_death' => ['required', 'in:'.implode(',', config('vital.places'))], 'ward_or_area' => ['nullable', 'string', 'max:80'],
            'manner' => ['required', 'in:'.implode(',', config('vital.manners'))],
            'cause_a' => ['required', 'string', 'max:500'], 'interval_a' => ['nullable', 'string', 'max:40'], 'cause_b' => ['nullable', 'string', 'max:500'], 'interval_b' => ['nullable', 'string', 'max:40'],
            'cause_c' => ['nullable', 'string', 'max:500'], 'interval_c' => ['nullable', 'string', 'max:40'], 'cause_d' => ['nullable', 'string', 'max:500'], 'interval_d' => ['nullable', 'string', 'max:40'],
            'contributing' => ['nullable', 'string', 'max:500'], 'underlying_icd10' => ['nullable', 'string', 'max:12'], 'autopsy_done' => ['nullable', 'boolean'], 'pregnancy_status' => ['nullable', 'in:'.implode(',', config('vital.pregnancy_statuses'))],
        ]);
        $c = $this->deaths->draft($this->patient((int) $v['patient_id']), $v + ['branch_id' => $this->tenant->getBranchId()], $request->user());

        return redirect()->route('admin.vital.deaths.show', $c->public_id)->with('success', __('Certificate written. A doctor must certify the cause of death.'));
    }

    public function show(Request $request, string $death)
    {
        $this->allow($request, 'vital.death.view');
        $c = $this->find(VrDeathCertification::class, $death);
        $c->load('patient:id,first_name,last_name,enterprise_patient_no,date_of_birth,sex', 'medicolegalCase:id,public_id,number,status');
        $u = $request->user();

        return view('admin.vital.deaths.show', ['c' => $c, 'can' => ['certify' => $u->can('vital.death.certify'), 'issue' => $u->can('vital.death.issue') && $c->certifier_id !== $u->id, 'cancel' => $u->can('vital.death.cancel'), 'ml' => $u->can('vital.medicolegal.view')],
            'idTypes' => config('vital.id_types')]);
    }

    public function certify(Request $request, string $death)
    {
        $this->allow($request, 'vital.death.certify');
        $v = $request->validate(['registration_no' => ['nullable', 'string', 'max:40']]);
        $this->deaths->certify($this->find(VrDeathCertification::class, $death), $request->user(), $v['registration_no'] ?? null);

        return back()->with('success', __('Certified. The cause of death can no longer be edited.'));
    }

    public function issue(Request $request, string $death)
    {
        $this->allow($request, 'vital.death.issue');
        $v = $request->validate(['issued_to' => ['required', 'string', 'max:150'], 'issued_relation' => ['required', 'string', 'max:60'], 'issued_id_type' => ['required', 'in:'.implode(',', config('vital.id_types'))], 'issued_id_number' => ['required', 'string', 'max:40']]);
        $this->deaths->issue($this->find(VrDeathCertification::class, $death), $request->user(), $v['issued_to'], $v['issued_relation'], $v['issued_id_type'], $v['issued_id_number']);

        return back()->with('success', __('Certificate issued.'));
    }

    public function cancel(Request $request, string $death)
    {
        $this->allow($request, 'vital.death.cancel');
        $v = $request->validate(['cancel_reason' => ['required', 'string', 'max:255']]);
        $this->deaths->cancel($this->find(VrDeathCertification::class, $death), $v['cancel_reason'], $request->user());

        return back()->with('success', __('Certificate cancelled. It can be written again.'));
    }
}
