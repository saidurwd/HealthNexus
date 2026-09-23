<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Laboratory\LabOrderItem;
use App\Models\Laboratory\LabResult;
use App\Services\AuditLogger;
use App\Services\Laboratory\ResultAmendmentService;
use App\Services\Laboratory\ResultEntryService;
use App\Services\Laboratory\ResultValidationService;
use Illuminate\Http\Request;
use Modules\Laboratory\Http\Requests\Web\AmendLabResultRequest;
use Modules\Laboratory\Http\Requests\Web\EnterLabResultRequest;

class LabResultController extends Controller
{
    public function __construct(
        private readonly ResultEntryService $entry,
        private readonly ResultValidationService $validation,
        private readonly ResultAmendmentService $amendment,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(EnterLabResultRequest $request, LabOrderItem $item)
    {
        $this->authorize('create', LabResult::class);

        $result = $this->entry->enter($item, $request->validated(), $request->user());

        $this->auditLogger->log('ENTER_RESULT', LabResult::class, $result->id, null, $result->toArray(), $request);

        return redirect()->route('admin.lab.orders.show', $item->labOrder)->with('success', 'Result recorded.');
    }

    public function validateTechnical(Request $request, LabResult $result)
    {
        $this->authorize('validate', $result);

        $oldValues = $result->toArray();
        $this->validation->technicalValidate($result, $request->user());

        $this->auditLogger->log('TECHNICAL_VALIDATE', LabResult::class, $result->id, $oldValues, $result->fresh()->toArray(), $request);

        return redirect()->route('admin.lab.orders.show', $result->orderItem->labOrder)->with('success', 'Result technically validated.');
    }

    public function approve(Request $request, LabResult $result)
    {
        $this->authorize('approve', $result);

        $oldValues = $result->toArray();
        $this->validation->pathologistApprove($result, $request->user());

        $this->auditLogger->log('PATHOLOGIST_APPROVE', LabResult::class, $result->id, $oldValues, $result->fresh()->toArray(), $request);

        return redirect()->route('admin.lab.orders.show', $result->orderItem->labOrder)->with('success', 'Result approved.');
    }

    public function amend(AmendLabResultRequest $request, LabResult $result)
    {
        $this->authorize('amend', $result);

        $amended = $this->amendment->amend($result, $request->validated(), $request->validated('reason'), $request->user());

        $this->auditLogger->log('AMEND', LabResult::class, $amended->id, $result->toArray(), $amended->toArray(), $request);

        return redirect()->route('admin.lab.orders.show', $result->orderItem->labOrder)->with('success', 'Result amended — a new version has been recorded.');
    }
}
