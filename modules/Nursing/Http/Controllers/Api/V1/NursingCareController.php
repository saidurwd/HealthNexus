<?php

namespace Modules\Nursing\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingEscalation;
use App\Models\Nursing\NursingHandover;
use App\Models\Nursing\NursingNote;
use App\Models\Nursing\NursingTask;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingEscalationService;
use App\Services\Nursing\NursingHandoverService;
use App\Services\Nursing\NursingNoteService;
use App\Services\Nursing\NursingTaskService;
use App\Services\Nursing\NursingVitalService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Episodes, vitals, tasks, notes, handover and escalation endpoints. Each action binds the
 * episode/record and authorizes through its policy so ward/hospital scope is enforced server-side.
 */
class NursingCareController extends Controller
{
    public function __construct(
        private readonly NursingVitalService $vitals,
        private readonly NursingTaskService $tasks,
        private readonly NursingNoteService $notes,
        private readonly NursingHandoverService $handovers,
        private readonly NursingEscalationService $escalations,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function dashboard(Request $request)
    {
        $this->authorize('viewAny', NursingEpisode::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        return ApiResponse::success([
            'active_episodes' => NursingEpisode::forTenant($companyId, $branchId)->where('status', 'active')->count(),
            'pending_tasks' => NursingTask::forTenant($companyId, $branchId)->where('status', 'pending')->count(),
            'open_escalations' => NursingEscalation::forTenant($companyId, $branchId)->whereNull('resolved_at')->count(),
        ]);
    }

    public function episodes(Request $request)
    {
        $this->authorize('viewAny', NursingEpisode::class);

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        return ApiResponse::paginated(NursingEpisode::forTenant($companyId, $branchId)->with('patient')->latest('start_at')->paginate(20));
    }

    public function episode(NursingEpisode $episode)
    {
        $this->authorize('view', $episode);

        return ApiResponse::success($episode->load(['patient', 'currentAssignments.nurse']));
    }

    public function storeVital(Request $request, NursingEpisode $episode)
    {
        $this->authorize('view', $episode);
        Gate::authorize('nursing.vitals.create');

        $validated = $request->validate([
            'temperature' => ['nullable', 'numeric'], 'systolic' => ['nullable', 'integer'], 'diastolic' => ['nullable', 'integer'],
            'pulse_rate' => ['nullable', 'integer'], 'respiratory_rate' => ['nullable', 'integer'],
            'height' => ['nullable', 'numeric'], 'weight' => ['nullable', 'numeric'],
            'oxygen_saturation' => ['nullable', 'integer'], 'notes' => ['nullable', 'string'],
        ]);

        $vital = $this->vitals->record($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', \App\Models\VitalSign::class, $vital->id, null, $vital->toArray(), $request);

        return ApiResponse::success($vital, 'Vitals recorded.', 201);
    }

    public function storeTask(Request $request, NursingEpisode $episode)
    {
        $this->authorize('view', $episode);
        Gate::authorize('nursing.task.create');

        $validated = $request->validate([
            'task_type' => ['required', 'string', 'max:255'], 'due_at' => ['required', 'date'],
            'priority' => ['nullable', 'string'], 'assigned_nurse_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $task = $this->tasks->create($episode, $validated);

        $this->auditLogger->log('CREATE', NursingTask::class, $task->id, null, $task->toArray(), $request);

        return ApiResponse::success($task, 'Task created.', 201);
    }

    public function completeTask(Request $request, NursingTask $task)
    {
        $this->authorize('view', $task->episode);
        Gate::authorize('nursing.task.complete');

        try {
            $task = $this->tasks->complete($task, $request->user(), $request->input('outcome'), $request->input('notes'));
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('COMPLETE', NursingTask::class, $task->id, null, ['status' => 'completed'], $request);

        return ApiResponse::success($task, 'Task completed.');
    }

    public function storeNote(Request $request, NursingEpisode $episode)
    {
        $this->authorize('view', $episode);
        $this->authorize('create', NursingNote::class);

        $validated = $request->validate(['note_type' => ['required', 'string'], 'content' => ['required', 'string']]);

        $note = $this->notes->create($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', NursingNote::class, $note->id, null, $note->toArray(), $request);

        return ApiResponse::success($note, 'Note saved.', 201);
    }

    public function finalizeNote(Request $request, NursingNote $note)
    {
        $this->authorize('finalize', $note);

        try {
            $note = $this->notes->finalize($note, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('FINALIZE', NursingNote::class, $note->id, null, ['status' => 'final'], $request);

        return ApiResponse::success($note, 'Note finalized.');
    }

    public function amendNote(Request $request, NursingNote $note)
    {
        $this->authorize('amend', $note);

        $validated = $request->validate(['content' => ['required', 'string']]);

        try {
            $amendment = $this->notes->addAddendum($note, $validated['content'], $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('AMEND', NursingNote::class, $note->id, null, $amendment->toArray(), $request);

        return ApiResponse::success($amendment, 'Addendum added.', 201);
    }

    public function storeHandover(Request $request, NursingEpisode $episode)
    {
        $this->authorize('view', $episode);
        $this->authorize('create', NursingHandover::class);

        $handover = $this->handovers->prepare($episode, $request->user(), $request->input('shift_id'));

        $this->auditLogger->log('CREATE', NursingHandover::class, $handover->id, null, $handover->toArray(), $request);

        return ApiResponse::success($handover, 'Handover prepared.', 201);
    }

    public function acknowledgeHandover(Request $request, NursingHandover $handover)
    {
        $this->authorize('acknowledge', $handover);

        try {
            $handover = $this->handovers->acknowledge($handover, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        $this->auditLogger->log('ACKNOWLEDGE', NursingHandover::class, $handover->id, null, ['status' => 'acknowledged'], $request);

        return ApiResponse::success($handover, 'Handover acknowledged.');
    }

    public function storeEscalation(Request $request, NursingEpisode $episode)
    {
        $this->authorize('view', $episode);
        $this->authorize('create', NursingEscalation::class);

        $validated = $request->validate([
            'concern' => ['required', 'string'], 'recipient_type' => ['required', 'string'],
            'recipient_id' => ['nullable', 'integer'], 'severity' => ['nullable', 'string'],
        ]);

        $escalation = $this->escalations->create($episode, $validated, $request->user());

        $this->auditLogger->log('CREATE', NursingEscalation::class, $escalation->id, null, $escalation->toArray(), $request);

        return ApiResponse::success($escalation, 'Escalation raised.', 201);
    }

    public function acknowledgeEscalation(Request $request, NursingEscalation $escalation)
    {
        $this->authorize('acknowledge', $escalation);

        try {
            $escalation = $this->escalations->acknowledge($escalation, $request->user());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($escalation, 'Escalation acknowledged.');
    }

    public function resolveEscalation(Request $request, NursingEscalation $escalation)
    {
        $this->authorize('resolve', $escalation);

        $validated = $request->validate(['action_taken' => ['required', 'string']]);

        try {
            $escalation = $this->escalations->resolve($escalation, $request->user(), $validated['action_taken']);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }

        return ApiResponse::success($escalation, 'Escalation resolved.');
    }
}
