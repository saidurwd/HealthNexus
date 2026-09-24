<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdBedBlock;
use App\Models\Ipd\IpdBedType;
use App\Models\Ipd\IpdRoom;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdBedBlockService;
use App\Services\Ipd\IpdBedService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IpdBedController extends Controller
{
    public function __construct(
        private readonly IpdBedService $beds,
        private readonly IpdBedBlockService $blocks,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', IpdBed::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $beds = IpdBed::query()
            ->forTenant($companyId, $branchId)
            ->with(['room.ward', 'bedType'])
            ->when($request->filled('room_id'), fn ($q) => $q->where('room_id', $request->input('room_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('bed_code', 'like', '%'.$request->input('search').'%'))
            ->orderBy('room_id')
            ->orderBy('bed_code')
            ->paginate(30)
            ->withQueryString();

        return view('admin.ipd.beds.index', compact('beds'));
    }

    public function create()
    {
        $this->authorize('create', IpdBed::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $rooms = IpdRoom::query()->forTenant($companyId)->where('is_active', true)->with('ward')->orderBy('room_number')->get();
        $bedTypes = IpdBedType::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.ipd.beds.create', compact('rooms', 'bedTypes'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', IpdBed::class);

        $validated = $request->validate($this->rules());
        $validated['company_id'] = $request->input('company_id');
        $validated['branch_id'] = $request->input('branch_id');

        $bed = $this->beds->create($validated, $request->user());

        $this->auditLogger->log('CREATE', IpdBed::class, $bed->id, null, $bed->toArray(), $request);

        return redirect()->route('admin.ipd.beds.index')->with('success', 'Bed created.');
    }

    public function edit(IpdBed $bed)
    {
        $this->authorize('update', $bed);

        $rooms = IpdRoom::query()->forTenant($bed->company_id)->where('is_active', true)->with('ward')->orderBy('room_number')->get();
        $bedTypes = IpdBedType::query()->forTenant($bed->company_id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.ipd.beds.edit', compact('bed', 'rooms', 'bedTypes'));
    }

    public function update(Request $request, IpdBed $bed)
    {
        $this->authorize('update', $bed);

        $validated = $request->validate($this->rules());

        $oldValues = $bed->toArray();
        $this->beds->update($bed, $validated, $request->user());

        $this->auditLogger->log('UPDATE', IpdBed::class, $bed->id, $oldValues, $bed->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.beds.index')->with('success', 'Bed updated.');
    }

    public function block(Request $request, IpdBed $bed)
    {
        $this->authorize('block', $bed);

        $validated = $request->validate([
            'reason_type' => ['required', 'string', 'max:50'],
            'reason' => ['required', 'string', 'max:500'],
            'expected_end_at' => ['nullable', 'date'],
        ]);

        try {
            $block = $this->blocks->block($bed, $validated['reason_type'], $validated['reason'], $request->user(), $validated['expected_end_at'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('BLOCK', IpdBedBlock::class, $block->id, null, $block->toArray(), $request);

        return redirect()->route('admin.ipd.beds.index')->with('success', 'Bed blocked.');
    }

    public function unblock(Request $request, IpdBedBlock $block)
    {
        $this->authorize('unblock', $block->bed);

        $oldValues = $block->toArray();

        try {
            $this->blocks->unblock($block, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('UNBLOCK', IpdBedBlock::class, $block->id, $oldValues, $block->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.beds.index')->with('success', 'Bed unblocked.');
    }

    private function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:ipd_rooms,id'],
            'bed_type_id' => ['nullable', 'integer', 'exists:ipd_bed_types,id'],
            'bed_code' => ['required', 'string', 'max:50'],
            'bed_name' => ['nullable', 'string', 'max:100'],
            'gender_type' => ['required', 'string', 'in:any,male,female'],
            'isolation_capable' => ['boolean'],
            'icu_capable' => ['boolean'],
            'ventilator_capable' => ['boolean'],
            'oxygen_available' => ['boolean'],
            'monitor_available' => ['boolean'],
            'is_vip' => ['boolean'],
            'is_pediatric' => ['boolean'],
            'is_maternity' => ['boolean'],
            'is_bariatric' => ['boolean'],
            'is_accessible' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
