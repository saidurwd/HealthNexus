<?php

namespace Modules\Mch\Http\Controllers\Web;

use App\Models\Patient;
use App\Services\Mch\ImmunisationService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

class ImmunisationController extends MchController
{
    public function __construct(TenantContextResolver $tenant, private readonly ImmunisationService $immunisation)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'mch.immunisation.view');
        $due = $this->immunisation->dueList($this->organizationId());
        $names = Patient::query()->where('organization_id', $this->organizationId())->whereIn('id', $due->pluck('patient_id'))->get(['id', 'first_name', 'last_name', 'enterprise_patient_no', 'date_of_birth'])->keyBy('id');

        return view('admin.mch.immunisation.index', ['rows' => $due->take(100), 'total' => $due->count(), 'names' => $names]);
    }

    public function card(Request $request, int $child)
    {
        $this->allow($request, 'mch.immunisation.view');
        $c = $this->patient($child);

        return view('admin.mch.immunisation.card', ['child' => $c, 'card' => $this->immunisation->card($c), 'can' => ['give' => $request->user()->can('mch.immunisation.give')], 'ready' => $c->date_of_birth !== null]);
    }

    public function give(Request $request, int $child)
    {
        $this->allow($request, 'mch.immunisation.give');
        $v = $request->validate(['vaccine' => ['required', 'string', 'max:20'], 'given_at' => ['nullable', 'date'], 'batch_no' => ['nullable', 'string', 'max:40'], 'site' => ['nullable', 'string', 'max:30'], 'status' => ['nullable', 'in:given,recorded'], 'note' => ['nullable', 'string', 'max:255']]);
        $this->immunisation->give($this->patient($child), $v['vaccine'], $v + ['branch_id' => $this->tenant->getBranchId()], $request->user());

        return back()->with('success', __('Dose recorded.'));
    }
}
