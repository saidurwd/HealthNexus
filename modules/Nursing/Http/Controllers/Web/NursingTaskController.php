<?php

namespace Modules\Nursing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nursing\NursingEpisode;
use App\Models\Nursing\NursingTask;
use App\Services\AuditLogger;
use App\Services\Nursing\NursingTaskService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class NursingTaskController extends Controller
{
    public function __construct(
        private readonly NursingTaskService $tasks,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('nursing.task.view');

        $companyId = app(TenantContextResolver::class)->getCompanyId();
        $branchId = app(TenantContextResolver::class)->getBranchId();

        $tasks = NursingTask::forTenant($companyId, $branchId)
            ->with(['patient', 'admission', 'assignedNurse'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_nurse_id', $request->user()->id))
            ->orderBy('due_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.nursing.tasks.index', compact('tasks'));
    }

    public function store(Request $request, NursingEpisode $episode)
    {
        Gate::authorize('nursing.task.create');

        $validated = $request->validate([
            'care_plan_id' => ['nullable', 'integer', 'exists:nursing_care_plans,id'],
            'intervention_id' => ['nullable', 'integer', 'exists:nursing_care_plan_interventions,id'],
            'task_type' => ['required', 'string', 'max:255'],
            'due_at' => ['required', 'date'],
            'priority' => ['nullable', 'string', 'in:routine,urgent,stat'],
            'assigned_nurse_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $task = $this->tasks->create($episode, $validated);

        $this->auditLogger->log('CREATE', NursingTask::class, $task->id, null, $task->toArray(), $request);

        return back()->with('success', 'Task created.');
    }

    public function complete(Request $request, NursingTask $task)
    {
        Gate::authorize('nursing.task.complete');

        try {
            $this->tasks->complete($task, $request->user(), $request->input('outcome'), $request->input('notes'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('COMPLETE', NursingTask::class, $task->id, null, ['status' => 'completed'], $request);

        return back()->with('success', 'Task completed.');
    }

    public function skip(Request $request, NursingTask $task)
    {
        Gate::authorize('nursing.task.complete');

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $this->tasks->skip($task, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('SKIP', NursingTask::class, $task->id, null, $validated, $request);

        return back()->with('success', 'Task skipped.');
    }

    public function refuse(Request $request, NursingTask $task)
    {
        Gate::authorize('nursing.task.complete');

        $validated = $request->validate(['reason' => ['required', 'string']]);

        try {
            $this->tasks->refuse($task, $request->user(), $validated['reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->auditLogger->log('REFUSE', NursingTask::class, $task->id, null, $validated, $request);

        return back()->with('success', 'Task refused by patient.');
    }
}
