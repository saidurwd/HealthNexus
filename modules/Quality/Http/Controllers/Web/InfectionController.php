<?php

namespace Modules\Quality\Http\Controllers\Web;

use App\Models\Patient;
use App\Models\Quality\QaHandHygieneAudit;
use App\Models\Quality\QaInfectionCase;
use App\Models\Quality\QaIsolation;
use App\Models\Quality\QaOutbreak;
use App\Services\Quality\InfectionService;
use App\Services\Quality\QualityPolicy;
use App\Services\Quality\QualityScope;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Infection control: infections, isolation, hand-hygiene rounds and outbreaks. */
class InfectionController extends QualityController
{
    public function __construct(TenantContextResolver $tenant, private readonly InfectionService $infection, private readonly QualityPolicy $policy, private readonly QualityScope $scope)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'quality.infection.view');
        $org = $this->organizationId();
        $u = $request->user();
        $from = today()->subMonths(3)->toDateString();

        return view('admin.quality.infection', [
            'cases' => $this->scope->apply(QaInfectionCase::query(), $u, $org)->with('patient')->orderByDesc('onset_on')->limit(50)->get(),
            'isolations' => $this->scope->apply(QaIsolation::query(), $u, $org)->with('patient')->whereNull('ended_at')->orderByDesc('started_at')->get(),
            'outbreaks' => $this->scope->apply(QaOutbreak::query(), $u, $org)->where('status', '!=', 'closed')->orderByDesc('opened_on')->get(),
            'rounds' => $this->scope->apply(QaHandHygieneAudit::query(), $u, $org)->where('audited_on', '>=', $from)->orderByDesc('audited_on')->limit(30)->get(),
            'compliance' => $this->infection->handHygiene($org, $from, today()->toDateString()),
            'wards' => DB::table('ipd_wards')->where('organization_id', $org)->orderBy('name')->get(['id', 'name']),
            'policy' => $this->policy, 'service' => $this->infection, 'canManage' => $u->can('quality.infection.manage'),
        ]);
    }

    public function storeCase(Request $request)
    {
        $this->allow($request, 'quality.infection.manage');
        $d = $request->validate(['patient_no' => ['required', 'string', 'max:40'], 'hai_type' => ['required', 'string', 'max:12'], 'organism' => ['nullable', 'string', 'max:80'], 'device' => ['nullable', 'string', 'max:30'], 'onset_on' => ['required', 'date'], 'ward_id' => ['nullable', 'integer'], 'note' => ['nullable', 'string', 'max:500']]);
        $patient = $this->patient($d['patient_no']);
        $admission = DB::table('ipd_admissions')->where('organization_id', $this->organizationId())->where('patient_id', $patient->id)->whereNull('actual_discharge_date')->orderByDesc('admitted_at')->first()
            ?? DB::table('ipd_admissions')->where('organization_id', $this->organizationId())->where('patient_id', $patient->id)->orderByDesc('admitted_at')->first();
        $this->infection->reportCase($this->organizationId(), $d + ['patient_id' => $patient->id, 'admission_id' => $admission->id ?? null, 'branch_id' => $this->branchId()], $request->user());

        return back()->with('success', __('The case was recorded.'));
    }

    public function decide(Request $request, string $case)
    {
        $this->allow($request, 'quality.infection.manage');
        $d = $request->validate(['decision' => ['required', 'in:confirm,rule_out'], 'note' => ['nullable', 'string', 'max:500']]);
        $this->infection->decide($this->find(QaInfectionCase::class, $case), $request->user(), $d['decision'] === 'confirm', $d['note'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    public function isolate(Request $request)
    {
        $this->allow($request, 'quality.infection.manage');
        $d = $request->validate(['patient_no' => ['required', 'string', 'max:40'], 'precaution' => ['required', 'string', 'max:12'], 'reason' => ['required', 'string', 'max:200']]);
        $this->infection->isolate($this->organizationId(), $this->patient($d['patient_no']), $d['precaution'], $d['reason'], $request->user());

        return back()->with('success', __('The precaution is in place.'));
    }

    public function endIsolation(Request $request, string $isolation)
    {
        $this->allow($request, 'quality.infection.manage');
        $this->infection->endIsolation($this->find(QaIsolation::class, $isolation), $request->user());

        return back()->with('success', __('The precaution has ended.'));
    }

    public function handHygiene(Request $request)
    {
        $this->allow($request, 'quality.infection.manage');
        $d = $request->validate(['ward_id' => ['nullable', 'integer'], 'staff_role' => ['required', 'string', 'max:12'], 'moments_observed' => ['required', 'integer', 'min:1', 'max:5000'], 'moments_compliant' => ['required', 'integer', 'min:0', 'max:5000'], 'audited_on' => ['nullable', 'date'], 'note' => ['nullable', 'string', 'max:300']]);
        $ward = ! empty($d['ward_id']) ? DB::table('ipd_wards')->where('organization_id', $this->organizationId())->where('id', $d['ward_id'])->first() : null;
        $this->infection->auditHandHygiene($this->organizationId(), $d + ['ward_name' => $ward->name ?? null, 'branch_id' => $this->branchId()], $request->user());

        return back()->with('success', __('The round was recorded.'));
    }

    public function closeOutbreak(Request $request, string $outbreak)
    {
        $this->allow($request, 'quality.infection.manage');
        $d = $request->validate(['note' => ['required', 'string', 'max:500'], 'contained' => ['nullable', 'boolean']]);
        $this->infection->closeOutbreak($this->find(QaOutbreak::class, $outbreak), $request->user(), $d['note'], (bool) ($d['contained'] ?? false));

        return back()->with('success', __('Saved.'));
    }

    private function patient(string $number): Patient
    {
        return Patient::query()->where('organization_id', $this->organizationId())->where('enterprise_patient_no', trim($number))->first()
            ?? throw ValidationException::withMessages(['patient_no' => __('No patient has that number.')]);
    }
}
