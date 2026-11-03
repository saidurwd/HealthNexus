<?php

namespace Modules\Vital\Http\Controllers\Web;

use App\Models\Vital\VrMedicolegalCase;
use App\Services\TenantContextResolver;
use App\Services\Vital\MedicoLegalService;
use Illuminate\Http\Request;

/** Police cases. The whole section is for people with the medico-legal right: what happened to a person is not for everyone. */
class MedicoLegalController extends VitalController
{
    public function __construct(TenantContextResolver $tenant, private readonly MedicoLegalService $cases)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'vital.medicolegal.view');
        $rows = VrMedicolegalCase::query()->with('patient:id,first_name,last_name,enterprise_patient_no')->where('organization_id', $this->organizationId())->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByRaw("case status when 'closed' then 1 else 0 end")->orderBy('brought_at')->paginate(25)->withQueryString();

        return view('admin.vital.medicolegal.index', ['rows' => $rows, 'late' => $this->cases->notInformedInTime($this->organizationId())->pluck('id')->all(), 'can' => ['register' => $request->user()->can('vital.medicolegal.register')]]);
    }

    public function create(Request $request)
    {
        $this->allow($request, 'vital.medicolegal.register');

        return view('admin.vital.medicolegal.create');
    }

    public function store(Request $request)
    {
        $this->allow($request, 'vital.medicolegal.register');
        $v = $request->validate(['patient_id' => ['required', 'integer'], 'kind' => ['required', 'in:'.implode(',', config('vital.medicolegal_kinds'))], 'brought_at' => ['required', 'date'], 'occurred_at' => ['nullable', 'date'],
            'brought_by' => ['nullable', 'string', 'max:150'], 'description' => ['required', 'string', 'max:3000'], 'injuries' => ['nullable', 'string', 'max:3000']]);
        $case = $this->cases->register($this->patient((int) $v['patient_id']), $v + ['branch_id' => $this->tenant->getBranchId()], $request->user());

        return redirect()->route('admin.vital.medicolegal.show', $case->public_id)->with('success', __('Case registered. Tell the police and record the General Diary number.'));
    }

    public function show(Request $request, string $case)
    {
        $this->allow($request, 'vital.medicolegal.view');
        $c = $this->find(VrMedicolegalCase::class, $case);
        $c->load('patient:id,first_name,last_name,enterprise_patient_no');

        return view('admin.vital.medicolegal.show', ['c' => $c, 'can' => ['manage' => $request->user()->can('vital.medicolegal.manage')], 'late' => $c->status === 'open' && $c->brought_at->lte(now()->subHours((int) config('vital.police_inform_hours')))]);
    }

    public function inform(Request $request, string $case)
    {
        $this->allow($request, 'vital.medicolegal.manage');
        $v = $request->validate(['police_station' => ['required', 'string', 'max:120'], 'gd_number' => ['required', 'string', 'max:40'], 'case_number' => ['nullable', 'string', 'max:40'], 'officer_name' => ['nullable', 'string', 'max:150'],
            'officer_contact' => ['nullable', 'string', 'max:60'], 'informed_at' => ['nullable', 'date']]);
        $this->cases->informPolice($this->find(VrMedicolegalCase::class, $case), $v, $request->user());

        return back()->with('success', __('The police are recorded as told.'));
    }

    public function postmortem(Request $request, string $case)
    {
        $this->allow($request, 'vital.medicolegal.manage');
        $c = $this->find(VrMedicolegalCase::class, $case);
        $request->boolean('done') ? $this->cases->recordPostmortem($c, $request->user()) : $this->cases->requestPostmortem($c, $request->user());

        return back()->with('success', $request->boolean('done') ? __('Post-mortem recorded.') : __('Post-mortem asked for.'));
    }

    public function close(Request $request, string $case)
    {
        $this->allow($request, 'vital.medicolegal.manage');
        $v = $request->validate(['closure_note' => ['required', 'string', 'max:255']]);
        $this->cases->close($this->find(VrMedicolegalCase::class, $case), $v['closure_note'], $request->user());

        return back()->with('success', __('Case closed.'));
    }
}
