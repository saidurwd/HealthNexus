<?php

namespace Modules\Facility\Http\Controllers\Web;

use App\Models\Facility\FacDietOrder;
use App\Models\Facility\FacDietType;
use App\Models\Facility\FacMealList;
use App\Models\Facility\FacMealTray;
use App\Services\Facility\DietService;
use App\Services\Facility\FacilityPolicy;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Diet orders, the meal lists and the kitchen's day. */
class DietController extends FacilityController
{
    public function __construct(TenantContextResolver $tenant, private readonly DietService $diet, private readonly FacilityPolicy $policy)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'facility.diet.view');
        $org = $this->organizationId();
        $admitted = DB::table('ipd_admissions as a')->join('patients as p', 'p.id', '=', 'a.patient_id')->where('a.organization_id', $org)->whereNull('a.actual_discharge_date')->whereNotIn('a.status', ['discharged', 'closed', 'cancelled'])
            ->orderBy('p.first_name')->get(['a.id', 'p.first_name', 'p.last_name', 'p.enterprise_patient_no']);

        return view('admin.facility.diets', ['orders' => FacDietOrder::query()->with('diet', 'patient')->where('organization_id', $org)->where('status', 'active')->orderBy('ward_id')->orderBy('id')->paginate(30), 'types' => FacDietType::query()->where('organization_id', $org)->where('active', true)->orderBy('code')->get(),
            'textures' => $this->policy->list('diet.textures'), 'admitted' => $admitted, 'wards' => DB::table('ipd_wards')->where('organization_id', $org)->orderBy('name')->get(['id', 'name']), 'requisition' => $this->diet->requisition($org, today()->toDateString()),
            'can' => ['order' => $request->user()->can('facility.diet.order'), 'kitchen' => $request->user()->can('facility.diet.kitchen')]]);
    }

    public function type(Request $request)
    {
        $this->allow($request, 'facility.diet.kitchen');
        $d = $request->validate(['code' => ['required', 'string', 'max:20'], 'name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:300']]);
        $this->diet->type($this->organizationId(), $d['code'], $d['name'], $d['description'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    public function order(Request $request)
    {
        $this->allow($request, 'facility.diet.order');
        $d = $request->validate(['admission_id' => ['required', 'integer'], 'diet_type_id' => ['required', 'integer'], 'texture' => ['required', 'string', 'max:14'], 'allergies' => ['nullable', 'string', 'max:300'], 'note' => ['nullable', 'string', 'max:300'], 'ward_id' => ['nullable', 'integer']]);
        $type = FacDietType::query()->where('organization_id', $this->organizationId())->findOrFail($d['diet_type_id']);
        $this->diet->order($this->organizationId(), (int) $d['admission_id'], $type, $d, $request->user());

        return back()->with('success', __('The diet was ordered.'));
    }

    public function stop(Request $request, string $order)
    {
        $this->allow($request, 'facility.diet.order');
        $this->diet->stop($this->find(FacDietOrder::class, $order), $request->user());

        return back()->with('success', __('The diet was stopped.'));
    }

    public function meals(Request $request)
    {
        $this->allow($request, 'facility.diet.view');
        $d = $request->validate(['date' => ['nullable', 'date'], 'meal' => ['nullable', 'string', 'max:10'], 'ward_id' => ['nullable', 'integer']]);
        $date = $d['date'] ?? today()->toDateString();
        $meal = $d['meal'] ?? 'lunch';
        $list = $this->diet->mealList($this->organizationId(), $date, $meal, isset($d['ward_id']) ? (int) $d['ward_id'] : null, $this->branchId());

        return view('admin.facility.meals', ['list' => $list->load('trays.order.diet', 'trays.order.patient'), 'date' => $date, 'meal' => $meal, 'meals' => $this->policy->list('diet.meals'), 'wards' => DB::table('ipd_wards')->where('organization_id', $this->organizationId())->orderBy('name')->get(['id', 'name']),
            'wardId' => $d['ward_id'] ?? null, 'canServe' => $request->user()->can('facility.diet.kitchen')]);
    }

    public function serve(Request $request, int $tray)
    {
        $this->allow($request, 'facility.diet.kitchen');
        $d = $request->validate(['status' => ['required', 'in:served,refused,held'], 'note' => ['nullable', 'string', 'max:200']]);
        $t = FacMealTray::query()->whereHas('order', fn ($q) => $q->where('organization_id', $this->organizationId()))->findOrFail($tray);
        $this->diet->serve($t, $d['status'], $d['note'] ?? null);

        return back()->with('success', __('Saved.'));
    }

    public function closeList(Request $request, string $list)
    {
        $this->allow($request, 'facility.diet.kitchen');
        $this->diet->closeList($this->find(FacMealList::class, $list), $request->user());

        return back()->with('success', __('The meal is closed.'));
    }
}
