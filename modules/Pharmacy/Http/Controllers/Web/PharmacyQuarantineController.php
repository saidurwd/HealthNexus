<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyQuarantine;
use App\Models\Pharmacy\PharmacyStore;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyQuarantineService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PharmacyQuarantineController extends Controller
{
    public function __construct(
        private readonly PharmacyQuarantineService $quarantine,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('pharmacy.quarantine.manage');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $quarantineRecords = PharmacyQuarantine::query()
            ->forTenant($companyId, $branchId)
            ->with(['store', 'medication', 'batch'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pharmacy.quarantine.index', compact('quarantineRecords'));
    }

    public function store(Request $request)
    {
        Gate::authorize('pharmacy.quarantine.manage');

        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:pharmacy_stores,id'],
            'batch_id' => ['required', 'integer', 'exists:pharmacy_batches,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason_type' => ['required', 'string', 'max:50'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $store = PharmacyStore::query()->findOrFail($validated['store_id']);
        $batch = PharmacyBatch::query()->findOrFail($validated['batch_id']);

        $record = $this->quarantine->quarantine($store, $batch, $validated['quantity'], $validated['reason_type'], $validated['reason'], $request->user());

        $this->auditLogger->log('QUARANTINE', PharmacyQuarantine::class, $record->id, null, $record->toArray(), $request);

        return redirect()->route('admin.pharmacy.quarantine.index')->with('success', 'Batch quarantined.');
    }

    public function release(Request $request, PharmacyQuarantine $quarantine)
    {
        Gate::authorize('pharmacy.quarantine.manage');

        $oldValues = $quarantine->toArray();

        try {
            $this->quarantine->release($quarantine, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('RELEASE', PharmacyQuarantine::class, $quarantine->id, $oldValues, $quarantine->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.quarantine.index')->with('success', 'Batch released from quarantine.');
    }

    public function dispose(Request $request, PharmacyQuarantine $quarantine)
    {
        Gate::authorize('pharmacy.quarantine.manage');

        $oldValues = $quarantine->toArray();

        try {
            $this->quarantine->dispose($quarantine, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('DISPOSE', PharmacyQuarantine::class, $quarantine->id, $oldValues, $quarantine->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.quarantine.index')->with('success', 'Quarantined stock disposed.');
    }
}
