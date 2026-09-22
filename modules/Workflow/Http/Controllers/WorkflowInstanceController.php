<?php

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WorkflowInstance;
use App\Services\ActivityLogger;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Http\Request;

class WorkflowInstanceController extends Controller
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * "My Approvals" — instances currently awaiting action from the logged-in user.
     */
    public function index(Request $request)
    {
        $pending = $this->engine->pendingApprovalsFor($request->user());

        $myRequests = WorkflowInstance::query()
            ->where('initiated_by', $request->user()->id)
            ->with(['workflow', 'currentStep'])
            ->latest()
            ->paginate(20, ['*'], 'requests');

        return view('admin.workflow.index', compact('pending', 'myRequests'));
    }

    public function show(WorkflowInstance $instance)
    {
        $instance->load(['workflow.steps.approvers', 'currentStep', 'initiatedBy', 'actions.performedBy', 'actions.step', 'subject']);

        $canAct = $this->engine->canAct($instance, auth()->user());

        return view('admin.workflow.show', compact('instance', 'canAct'));
    }

    public function approve(Request $request, WorkflowInstance $instance)
    {
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $this->engine->approve($instance, $request->user(), $validated['note'] ?? null);

        $this->activityLogger->log('WORKFLOW_APPROVED', 'Approved a workflow step', $instance, [], $request);

        return redirect()->route('admin.workflow.index')->with('success', 'Approved.');
    }

    public function reject(Request $request, WorkflowInstance $instance)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $this->engine->reject($instance, $request->user(), $validated['reason']);

        $this->activityLogger->log('WORKFLOW_REJECTED', 'Rejected a workflow instance', $instance, [], $request);

        return redirect()->route('admin.workflow.index')->with('success', 'Rejected.');
    }

    public function returnForRevision(Request $request, WorkflowInstance $instance)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $this->engine->returnForRevision($instance, $request->user(), $validated['reason']);

        $this->activityLogger->log('WORKFLOW_RETURNED', 'Returned a workflow instance for revision', $instance, [], $request);

        return redirect()->route('admin.workflow.index')->with('success', 'Returned for revision.');
    }
}
