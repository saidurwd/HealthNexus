<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyRecall;
use App\Services\AuditLogger;
use App\Services\Pharmacy\PharmacyRecallService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PharmacyRecallController extends Controller
{
    public function __construct(
        private readonly PharmacyRecallService $recalls,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('pharmacy.recall.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $recallRecords = PharmacyRecall::query()
            ->forTenant($companyId, $branchId)
            ->with(['medication', 'batch'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pharmacy.recalls.index', compact('recallRecords'));
    }

    public function store(Request $request)
    {
        Gate::authorize('pharmacy.recall.manage');

        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:pharmacy_batches,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $batch = PharmacyBatch::query()->findOrFail($validated['batch_id']);

        $recall = $this->recalls->initiate($batch, $validated['reason'], $request->user());

        $this->auditLogger->log('INITIATE', PharmacyRecall::class, $recall->id, null, $recall->toArray(), $request);

        return redirect()->route('admin.pharmacy.recalls.show', $recall)->with('success', 'Recall initiated; affected stock quarantined.');
    }

    public function show(PharmacyRecall $recall)
    {
        Gate::authorize('pharmacy.recall.view');

        $recall->load(['medication', 'batch']);
        $affectedItems = $this->recalls->affectedDispensingItems($recall->batch);

        return view('admin.pharmacy.recalls.show', compact('recall', 'affectedItems'));
    }

    public function close(Request $request, PharmacyRecall $recall)
    {
        Gate::authorize('pharmacy.recall.manage');

        $oldValues = $recall->toArray();

        try {
            $this->recalls->close($recall, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('CLOSE', PharmacyRecall::class, $recall->id, $oldValues, $recall->fresh()->toArray(), $request);

        return redirect()->route('admin.pharmacy.recalls.show', $recall)->with('success', 'Recall closed.');
    }
}
