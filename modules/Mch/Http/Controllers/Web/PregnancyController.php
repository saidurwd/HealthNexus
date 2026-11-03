<?php

namespace Modules\Mch\Http\Controllers\Web;

use App\Models\Mch\MchPregnancy;
use App\Services\Mch\PregnancyService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class PregnancyController extends MchController
{
    public function __construct(TenantContextResolver $tenant, private readonly PregnancyService $pregnancies)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'mch.pregnancy.view');
        $rows = MchPregnancy::query()->with('patient:id,first_name,last_name,enterprise_patient_no')->withCount('visits')->where('organization_id', $this->organizationId())->when($request->query('status', 'active'), fn ($q, $v) => $v === 'all' ? $q : $q->where('status', $v))
            ->when($request->query('due'), fn ($q) => $q->where('status', 'active')->whereBetween('edd', [now()->toDateString(), now()->addDays(30)->toDateString()]))->orderBy('edd')->paginate(25)->withQueryString();

        return view('admin.mch.pregnancies.index', ['rows' => $rows, 'status' => $request->query('status', 'active'), 'can' => ['book' => $request->user()->can('mch.pregnancy.book')]]);
    }

    public function create(Request $request)
    {
        $this->allow($request, 'mch.pregnancy.book');

        return view('admin.mch.pregnancies.create', ['risks' => config('mch.risk_factors')]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'mch.pregnancy.book');
        $v = $request->validate(['patient_id' => ['required', 'integer'], 'lmp' => ['nullable', 'date'], 'edd' => ['nullable', 'date'], 'gravida' => ['required', 'integer', 'between:1,20'], 'para' => ['required', 'integer', 'between:0,19'],
            'risk_factors' => ['nullable', 'array'], 'risk_factors.*' => ['string', 'max:40']]);
        $p = $this->pregnancies->book($this->patient((int) $v['patient_id']), $v + ['branch_id' => $this->tenant->getBranchId()], $request->user());

        return redirect()->route('admin.mch.pregnancies.show', $p->public_id)->with('success', __('Pregnancy booked.'));
    }

    public function show(Request $request, string $pregnancy)
    {
        $this->allow($request, 'mch.pregnancy.view');
        $p = $this->find(MchPregnancy::class, $pregnancy);
        $p->load('patient:id,first_name,last_name,enterprise_patient_no,date_of_birth', 'delivery.newborns');
        $u = $request->user();

        return view('admin.mch.pregnancies.show', ['p' => $p, 'visits' => $p->visits()->orderBy('visit_no')->get(), 'weeks' => $this->pregnancies->weeksOn($p), 'plan' => config('mch.anc_visit_weeks'),
            'signs' => config('mch.danger_signs'), 'proteins' => config('mch.urine_protein'), 'modes' => config('mch.delivery_modes'), 'complications' => config('mch.delivery_complications'), 'newbornOutcomes' => config('mch.newborn_outcomes'),
            'can' => ['visit' => $u->can('mch.anc.record'), 'deliver' => $u->can('mch.delivery.record'), 'close' => $u->can('mch.pregnancy.book')]]);
    }

    public function visit(Request $request, string $pregnancy)
    {
        $this->allow($request, 'mch.anc.record');
        $v = $request->validate(['visited_at' => ['nullable', 'date'], 'bp_systolic' => ['nullable', 'integer', 'between:60,260'], 'bp_diastolic' => ['nullable', 'integer', 'between:30,160'], 'weight_kg' => ['nullable', 'numeric', 'between:20,200'],
            'fundal_height_cm' => ['nullable', 'integer', 'between:5,50'], 'fetal_heart_rate' => ['nullable', 'integer', 'between:60,220'], 'hemoglobin' => ['nullable', 'numeric', 'between:2,20'], 'urine_protein' => ['nullable', 'string', 'max:10'],
            'tt_given' => ['nullable', 'boolean'], 'danger_signs' => ['nullable', 'array'], 'danger_signs.*' => ['string', 'max:40'], 'next_visit_on' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:500']]);
        $visit = $this->pregnancies->recordVisit($this->find(MchPregnancy::class, $pregnancy), $v, $request->user());

        return back()->with($visit->alerts ? 'warning' : 'success', $visit->alerts ? __('Visit recorded. Something needs attention: see the alerts.') : __('Visit recorded.'));
    }

    public function deliver(Request $request, string $pregnancy)
    {
        $this->allow($request, 'mch.delivery.record');
        $v = $request->validate(['delivered_at' => ['nullable', 'date'], 'mode' => ['required', 'string', 'max:16'], 'place' => ['nullable', 'in:'.implode(',', config('vital.places'))], 'attendant_name' => ['nullable', 'string', 'max:150'],
            'blood_loss_ml' => ['nullable', 'integer', 'between:0,6000'], 'complications' => ['nullable', 'array'], 'complications.*' => ['string', 'max:40'], 'mother_outcome' => ['nullable', 'in:'.implode(',', config('mch.mother_outcomes'))],
            'babies' => ['required', 'array', 'min:1', 'max:4'], 'babies.*.sex' => ['required', 'string', 'max:14'], 'babies.*.outcome' => ['nullable', 'string', 'max:12'], 'babies.*.weight_g' => ['nullable', 'integer', 'between:200,7000'],
            'babies.*.apgar_1' => ['nullable', 'integer', 'between:0,10'], 'babies.*.apgar_5' => ['nullable', 'integer', 'between:0,10'], 'babies.*.breastfed_within_hour' => ['nullable', 'boolean'], 'babies.*.vitamin_k_given' => ['nullable', 'boolean'],
            'babies.*.admitted_to' => ['nullable', 'in:nursery,nicu'], 'babies.*.name' => ['nullable', 'string', 'max:100']]);
        $this->pregnancies->deliver($this->find(MchPregnancy::class, $pregnancy), $v, $v['babies'], $request->user());

        return back()->with('success', __('Delivery recorded. The babies\' records and birth notifications were opened.'));
    }

    public function close(Request $request, string $pregnancy)
    {
        $this->allow($request, 'mch.pregnancy.book');
        $v = $request->validate(['end_reason' => ['required', 'string', 'max:20']]);
        $this->pregnancies->close($this->find(MchPregnancy::class, $pregnancy), $v['end_reason'], $request->user());

        return back()->with('success', __('Pregnancy closed.'));
    }
}
