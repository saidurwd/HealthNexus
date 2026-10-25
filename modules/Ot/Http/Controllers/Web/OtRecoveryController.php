<?php

namespace Modules\Ot\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ot\OtRecoveryRecord;
use App\Models\Ot\OtSurgery;
use App\Models\VitalSign;
use App\Services\AuditLogger;
use App\Services\Ot\OtRecoveryService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OtRecoveryController extends Controller
{
    public function __construct(
        private readonly OtRecoveryService $recovery,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function show(OtSurgery $surgery)
    {
        $this->authorize('view', $surgery);

        $recovery = $this->recovery->admitToRecovery($surgery);
        $recovery->load('surgery.encounter.vitals');

        return view('admin.ot.recovery.show', compact('recovery'));
    }

    public function recordVitals(Request $request, OtRecoveryRecord $recovery)
    {
        $this->authorize('record', $recovery);

        $validated = $request->validate([
            'temperature' => ['nullable', 'numeric'],
            'systolic' => ['nullable', 'integer'],
            'diastolic' => ['nullable', 'integer'],
            'pulse_rate' => ['nullable', 'integer'],
            'respiratory_rate' => ['nullable', 'integer'],
            'oxygen_saturation' => ['nullable', 'integer'],
        ]);

        $vital = $this->recovery->recordVitals($recovery, $validated, $request->user());

        $this->auditLogger->log('CREATE', VitalSign::class, $vital->id, null, $vital->toArray(), $request);

        return back()->with('success', 'Recovery vitals recorded.');
    }

    public function updateAssessment(Request $request, OtRecoveryRecord $recovery)
    {
        $this->authorize('record', $recovery);

        $validated = $request->validate([
            'pain_score' => ['nullable', 'string', 'max:50'],
            'consciousness' => ['nullable', 'string', 'max:255'],
            'airway_status' => ['nullable', 'string', 'max:255'],
            'nausea_vomiting' => ['nullable', 'string', 'max:255'],
            'complications' => ['nullable', 'string'],
            'discharge_criteria_met' => ['nullable', 'boolean'],
        ]);

        $this->recovery->updateAssessment($recovery, $validated);

        return back()->with('success', 'Recovery assessment updated.');
    }

    public function markReadyForTransfer(Request $request, OtRecoveryRecord $recovery)
    {
        $this->authorize('record', $recovery);

        try {
            $this->recovery->markReadyForTransfer($recovery);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Patient marked ready for transfer.');
    }

    public function completeTransfer(Request $request, OtRecoveryRecord $recovery)
    {
        $this->authorize('complete', $recovery);

        $validated = $request->validate(['destination' => ['required', 'string', 'in:ward,icu,hdu,discharge,other_facility']]);

        $this->recovery->completeTransfer($recovery, $validated['destination'], $request->user());

        $this->auditLogger->log('TRANSFER', OtRecoveryRecord::class, $recovery->id, null, $validated, $request);

        return back()->with('success', 'Patient transferred.');
    }
}
