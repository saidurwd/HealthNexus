<?php

namespace Modules\Radiology\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppointmentRoom;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Radiology\RadiologyModality;
use App\Models\Radiology\RadiologySection;
use App\Services\AuditLogger;
use App\Services\Radiology\RadiologyModalityService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RadiologyModalityController extends Controller
{
    public function __construct(
        private readonly RadiologyModalityService $modalities,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('radiology.modality.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $modalities = RadiologyModality::query()
            ->forTenant($companyId, $branchId)
            ->with(['section', 'room'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.radiology.modalities.index', compact('modalities'));
    }

    public function create()
    {
        Gate::authorize('radiology.modality.create');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $sections = RadiologySection::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();
        $rooms = AppointmentRoom::query()->where('is_active', true)->orderBy('name')->get();
        $companies = Company::all();
        $branches = Branch::all();

        return view('admin.radiology.modalities.create', compact('sections', 'rooms', 'companies', 'branches'));
    }

    public function store(Request $request)
    {
        Gate::authorize('radiology.modality.create');

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'section_id' => ['nullable', 'integer', 'exists:radiology_sections,id'],
            'room_id' => ['nullable', 'integer', 'exists:appointment_rooms,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'modality_type' => ['required', 'string', 'max:50'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'ae_title' => ['nullable', 'string', 'max:100'],
            'ip_address' => ['nullable', 'ip'],
            'port' => ['nullable', 'integer'],
            'pacs_endpoint' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:100'],
        ]);

        $modality = $this->modalities->create([...$validated, 'status' => 'offline', 'is_active' => true]);

        $this->auditLogger->log('CREATE', RadiologyModality::class, $modality->id, null, $modality->toArray(), $request);

        return redirect()->route('admin.radiology.modalities.index')->with('success', 'Modality registered.');
    }

    public function edit(RadiologyModality $modality)
    {
        Gate::authorize('radiology.modality.update');

        $sections = RadiologySection::query()->forTenant($modality->company_id)->where('is_active', true)->orderBy('name')->get();
        $rooms = AppointmentRoom::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.radiology.modalities.edit', compact('modality', 'sections', 'rooms'));
    }

    public function update(Request $request, RadiologyModality $modality)
    {
        Gate::authorize('radiology.modality.update');

        $validated = $request->validate([
            'section_id' => ['nullable', 'integer', 'exists:radiology_sections,id'],
            'room_id' => ['nullable', 'integer', 'exists:appointment_rooms,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'modality_type' => ['required', 'string', 'max:50'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'ae_title' => ['nullable', 'string', 'max:100'],
            'ip_address' => ['nullable', 'ip'],
            'port' => ['nullable', 'integer'],
            'pacs_endpoint' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:online,offline,maintenance,disabled'],
            'is_active' => ['boolean'],
        ]);

        $oldValues = $modality->toArray();
        $this->modalities->update($modality, $validated);

        $this->auditLogger->log('UPDATE', RadiologyModality::class, $modality->id, $oldValues, $modality->fresh()->toArray(), $request);

        return redirect()->route('admin.radiology.modalities.index')->with('success', 'Modality updated.');
    }
}
