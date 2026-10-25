<?php

namespace Modules\Icu\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Icu\IcuEpisode;
use App\Models\Icu\IcuProcedure;
use App\Services\Icu\IcuProcedureService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IcuProcedureController extends Controller
{
    public function __construct(private readonly IcuProcedureService $procedures) {}

    public function index()
    {
        Gate::authorize('icu.procedure.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $procedures = IcuProcedure::forTenant($companyId, $branchId)->orderBy('name')->paginate(20);

        return view('admin.icu.procedures.index', compact('procedures'));
    }

    public function create()
    {
        Gate::authorize('icu.procedure.view');

        return view('admin.icu.procedures.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('icu.procedure.view');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string'],
        ]);

        $companyId = app(TenantContextResolver::class)->getCompanyId();

        IcuProcedure::create([...$validated, 'company_id' => $companyId, 'is_active' => true]);

        return redirect()->route('admin.icu.procedures.index')->with('success', 'ICU procedure added.');
    }

    public function record(Request $request, IcuEpisode $episode)
    {
        Gate::authorize('icu.procedure.create');

        $validated = $request->validate([
            'procedure_id' => ['required', 'integer', 'exists:icu_procedures,id'],
            'indication' => ['nullable', 'string'],
            'findings' => ['nullable', 'string'],
            'complications' => ['nullable', 'string'],
            'performed_at' => ['nullable', 'date'],
        ]);

        $this->procedures->record($episode, $validated, $request->user());

        return redirect()->route('admin.icu.episodes.show', $episode)->with('success', 'Procedure recorded.');
    }
}
