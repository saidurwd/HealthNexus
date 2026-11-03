<?php

namespace Modules\Facility\Http\Controllers\Web;

use App\Models\Facility\FacGateLog;
use App\Models\Facility\FacVisitorBlock;
use App\Models\Facility\FacVisitorPass;
use App\Services\Facility\FacilityPolicy;
use App\Services\Facility\VisitorService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Visitor and attendant passes, the gate, and the people who are not allowed in. */
class VisitorController extends FacilityController
{
    public function __construct(TenantContextResolver $tenant, private readonly VisitorService $visitors, private readonly FacilityPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'facility.visitor.view');
        $org = $this->organizationId();
        $admitted = DB::table('ipd_admissions as a')->join('patients as p', 'p.id', '=', 'a.patient_id')->where('a.organization_id', $org)->whereNull('a.actual_discharge_date')->whereNotIn('a.status', ['discharged', 'closed', 'cancelled'])->orderBy('p.first_name')->get(['a.id', 'p.first_name', 'p.last_name', 'p.enterprise_patient_no']);

        return view('admin.facility.visitors', ['passes' => FacVisitorPass::query()->with('patient')->where('organization_id', $org)->where('status', 'active')->orderByDesc('id')->paginate(25), 'admitted' => $admitted, 'idTypes' => $this->policy->list('visitor.id_types'), 'service' => $this->visitors,
            'blocks' => FacVisitorBlock::query()->where('organization_id', $org)->where('active', true)->orderByDesc('id')->get(), 'hours' => (array) $this->policy->get('visitor.hours', []), 'can' => ['issue' => $request->user()->can('facility.visitor.issue'), 'manage' => $request->user()->can('facility.visitor.manage')]]);
    }

    public function issue(Request $request)
    {
        $this->allow($request, 'facility.visitor.issue');
        $d = $request->validate(['admission_id' => ['required', 'integer'], 'kind' => ['required', 'in:visitor,attendant'], 'visitor_name' => ['required', 'string', 'max:150'], 'phone' => ['nullable', 'string', 'max:30'], 'relation' => ['nullable', 'string', 'max:60'], 'id_type' => ['nullable', 'string', 'max:20'], 'id_number' => ['nullable', 'string', 'max:60']]);
        $pass = $this->visitors->issue($this->organizationId(), (int) $d['admission_id'], $d, $request->user());

        return back()->with('success', __('Pass :n is ready.', ['n' => $pass->number]));
    }

    public function gate(Request $request)
    {
        $this->allow($request, 'facility.visitor.gate');
        $number = trim((string) $request->query('number', ''));
        $pass = $number !== '' ? FacVisitorPass::query()->with('patient')->where('organization_id', $this->organizationId())->where('number', $number)->first() : null;

        return view('admin.facility.gate', ['number' => $number, 'pass' => $pass, 'why' => $pass ? $this->visitors->refusal($pass) : null, 'inside' => $pass ? $this->visitors->inside($pass) : false, 'service' => $this->visitors,
            'log' => FacGateLog::query()->with('pass')->where('organization_id', $this->organizationId())->orderByDesc('id')->limit(30)->get(), 'insideNow' => FacVisitorPass::query()->where('organization_id', $this->organizationId())->where('status', 'active')->get()->filter(fn ($p) => $this->visitors->inside($p))->count()]);
    }

    public function enter(Request $request)
    {
        $this->allow($request, 'facility.visitor.gate');
        $d = $request->validate(['number' => ['required', 'string', 'max:30'], 'gate' => ['nullable', 'string', 'max:40']]);
        $pass = $this->pass($d['number']);
        $this->visitors->enter($pass, $request->user(), $d['gate'] ?? null);

        return redirect()->route('admin.facility.gate')->with('success', __(':n may enter.', ['n' => $pass->visitor_name]));
    }

    public function leave(Request $request)
    {
        $this->allow($request, 'facility.visitor.gate');
        $d = $request->validate(['number' => ['required', 'string', 'max:30'], 'gate' => ['nullable', 'string', 'max:40']]);
        $this->visitors->leave($this->pass($d['number']), $request->user(), $d['gate'] ?? null);

        return redirect()->route('admin.facility.gate')->with('success', __('Out.'));
    }

    public function revoke(Request $request, string $pass)
    {
        $this->allow($request, 'facility.visitor.manage');
        $d = $request->validate(['reason' => ['required', 'string', 'max:200']]);
        $this->visitors->revoke($this->find(FacVisitorPass::class, $pass), $request->user(), $d['reason']);

        return back()->with('success', __('The pass was withdrawn.'));
    }

    public function block(Request $request)
    {
        $this->allow($request, 'facility.visitor.manage');
        $d = $request->validate(['name' => ['required', 'string', 'max:150'], 'phone' => ['nullable', 'string', 'max:30'], 'reason' => ['required', 'string', 'max:300']]);
        $this->visitors->block($this->organizationId(), $d['name'], $d['phone'] ?? null, $d['reason'], $request->user());

        return back()->with('success', __('Saved.'));
    }

    public function unblock(Request $request, int $block)
    {
        $this->allow($request, 'facility.visitor.manage');
        $this->visitors->unblock(FacVisitorBlock::query()->where('organization_id', $this->organizationId())->findOrFail($block));

        return back()->with('success', __('Saved.'));
    }

    private function pass(string $number): FacVisitorPass
    {
        return FacVisitorPass::query()->where('organization_id', $this->organizationId())->where('number', trim($number))->first() ?? throw ValidationException::withMessages(['number' => __('No pass has that number.')]);
    }
}
