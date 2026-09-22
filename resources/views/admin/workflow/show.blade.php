@extends('layouts.adminlte')

@section('page_title', 'Workflow Request')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $instance->workflow->name }}</h3>
        <div class="card-tools"><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $instance->status)) }}</span></div>
    </div>
    <div class="card-body">
        <table class="table table-sm">
            <tr><th>Subject</th><td>{{ class_basename($instance->subject_type) }} #{{ $instance->subject_id }}</td></tr>
            <tr><th>Initiated By</th><td>{{ $instance->initiatedBy?->name }}</td></tr>
            <tr><th>Current Step</th><td>{{ $instance->currentStep?->name ?? '-' }}</td></tr>
            <tr><th>Submitted At</th><td>{{ $instance->submitted_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
            <tr><th>Notes</th><td>{{ $instance->notes ?? '-' }}</td></tr>
        </table>

        @can('workflow.act')
            @if($canAct)
                <div class="d-flex gap-2 mt-3">
                    <form action="{{ route('admin.workflow.approve', $instance) }}" method="post">
                        @csrf
                        <button class="btn btn-success">Approve</button>
                    </form>
                    <button class="btn btn-warning" type="button" data-bs-toggle="collapse" data-bs-target="#returnForm">Return for Revision</button>
                    <button class="btn btn-danger" type="button" data-bs-toggle="collapse" data-bs-target="#rejectForm">Reject</button>
                </div>

                <div class="collapse mt-3" id="rejectForm">
                    <form action="{{ route('admin.workflow.reject', $instance) }}" method="post">
                        @csrf
                        <div class="mb-2"><textarea name="reason" class="form-control" placeholder="Reason for rejection" required></textarea></div>
                        <button class="btn btn-danger">Confirm Reject</button>
                    </form>
                </div>
                <div class="collapse mt-3" id="returnForm">
                    <form action="{{ route('admin.workflow.return', $instance) }}" method="post">
                        @csrf
                        <div class="mb-2"><textarea name="reason" class="form-control" placeholder="What needs to change?" required></textarea></div>
                        <button class="btn btn-warning">Confirm Return</button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Timeline</h3></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Action</th><th>Step</th><th>By</th><th>Note</th><th>At</th></tr>
            </thead>
            <tbody>
                @forelse($instance->actions as $action)
                    <tr>
                        <td>{{ ucfirst($action->action) }}</td>
                        <td>{{ $action->step?->name ?? '-' }}</td>
                        <td>{{ $action->performedBy?->name }}</td>
                        <td>{{ $action->note ?? '-' }}</td>
                        <td>{{ $action->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No actions recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Workflow Steps</h3></div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>#</th><th>Step</th><th>Eligible Approvers</th></tr></thead>
            <tbody>
                @foreach($instance->workflow->steps as $step)
                    <tr class="{{ $instance->current_step_id === $step->id ? 'table-active' : '' }}">
                        <td>{{ $step->step_order }}</td>
                        <td>{{ $step->name }}</td>
                        <td>
                            @foreach($step->approvers as $approver)
                                <span class="badge bg-secondary">
                                    @if($approver->approver_type === 'role') Role: {{ $approver->role_name }}
                                    @elseif($approver->approver_type === 'user') {{ $approver->user?->name }}
                                    @else Department: {{ $approver->department?->name }}
                                    @endif
                                </span>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop
