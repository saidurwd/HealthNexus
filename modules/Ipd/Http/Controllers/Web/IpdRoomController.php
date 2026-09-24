<?php

namespace Modules\Ipd\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ipd\IpdRoom;
use App\Models\Ipd\IpdWard;
use App\Services\AuditLogger;
use App\Services\Ipd\IpdRoomService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IpdRoomController extends Controller
{
    public function __construct(
        private readonly IpdRoomService $rooms,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('ipd.room.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $rooms = IpdRoom::query()
            ->forTenant($companyId, $branchId)
            ->with('ward')
            ->when($request->filled('ward_id'), fn ($q) => $q->where('ward_id', $request->input('ward_id')))
            ->orderBy('ward_id')
            ->orderBy('room_number')
            ->paginate(20)
            ->withQueryString();

        $wards = IpdWard::query()->forTenant($companyId, $branchId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.ipd.rooms.index', compact('rooms', 'wards'));
    }

    public function create()
    {
        Gate::authorize('ipd.room.create');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $wards = IpdWard::query()->forTenant($companyId)->where('is_active', true)->orderBy('name')->get();

        return view('admin.ipd.rooms.create', compact('wards'));
    }

    public function store(Request $request)
    {
        Gate::authorize('ipd.room.create');

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'ward_id' => ['required', 'integer', 'exists:ipd_wards,id'],
            'room_number' => ['required', 'string', 'max:50'],
            'room_type' => ['required', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'gender_policy' => ['required', 'string', 'in:any,male,female'],
            'isolation_capable' => ['boolean'],
            'is_vip' => ['boolean'],
            'rate_category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $room = $this->rooms->create($validated, $request->user());

        $this->auditLogger->log('CREATE', IpdRoom::class, $room->id, null, $room->toArray(), $request);

        return redirect()->route('admin.ipd.rooms.index')->with('success', 'Room created.');
    }

    public function edit(IpdRoom $room)
    {
        Gate::authorize('ipd.room.update');

        $wards = IpdWard::query()->forTenant($room->company_id)->where('is_active', true)->orderBy('name')->get();

        return view('admin.ipd.rooms.edit', compact('room', 'wards'));
    }

    public function update(Request $request, IpdRoom $room)
    {
        Gate::authorize('ipd.room.update');

        $validated = $request->validate([
            'ward_id' => ['required', 'integer', 'exists:ipd_wards,id'],
            'room_number' => ['required', 'string', 'max:50'],
            'room_type' => ['required', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'gender_policy' => ['required', 'string', 'in:any,male,female'],
            'isolation_capable' => ['boolean'],
            'is_vip' => ['boolean'],
            'rate_category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $oldValues = $room->toArray();
        $this->rooms->update($room, $validated, $request->user());

        $this->auditLogger->log('UPDATE', IpdRoom::class, $room->id, $oldValues, $room->fresh()->toArray(), $request);

        return redirect()->route('admin.ipd.rooms.index')->with('success', 'Room updated.');
    }
}
