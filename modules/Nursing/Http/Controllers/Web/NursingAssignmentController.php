<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingAssignment;
use App\Models\Nursing\NursingEpisode;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingAssignmentService;
use Illuminate\Http\Request;

class NursingAssignmentController extends Controller
{
    public function __construct(
        private readonly NursingAssignmentService $assignments,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(Request $request, NursingEpisode $episode)
    {
        $this->authorize('create', NursingAssignment::class);

        $validated = $request->validate([
            'nurse_id' => ['required', 'integer', 'exists:users,id'],
            'ward_id' => ['nullable', 'integer', 'exists:ipd_wards,id'],
            'bed_id' => ['nullable', 'integer', 'exists:ipd_beds,id'],
            'shift_id' => ['nullable', 'integer', 'exists:nursing_shifts,id'],
            'assignment_type' => ['nullable', 'string', 'in:patient,bed,ward,shift,charge_nurse,team'],
        ]);

        $assignment = $this->assignments->assign($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', NursingAssignment::class, $assignment->id, null, $assignment->toArray(), $request);

        return back()->with('success', 'Nurse assigned.');
    }

    public function destroy(Request $request, NursingAssignment $assignment)
    {
        $this->authorize('update', $assignment);

        $this->assignments->end($assignment, $request->user());

        $this->auditLogger->log('UPDATE', NursingAssignment::class, $assignment->id, null, ['ended_at' => now()], $request);

        return back()->with('success', 'Assignment ended.');
    }
}
