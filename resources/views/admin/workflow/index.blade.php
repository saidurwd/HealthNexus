@extends('layouts.adminlte')

@section('page_title', 'My Approvals')

@section('page_content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Pending My Approval</h3></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Workflow</th>
                    <th>Step</th>
                    <th>Subject</th>
                    <th>Initiated By</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($pending as $instance)
                    <tr>
                        <td>{{ $instance->workflow->name }}</td>
                        <td>{{ $instance->currentStep?->name }}</td>
                        <td>{{ class_basename($instance->subject_type) }} #{{ $instance->subject_id }}</td>
                        <td>{{ $instance->initiatedBy?->name }}</td>
                        <td>{{ $instance->submitted_at?->format('Y-m-d H:i') }}</td>
                        <td><a href="{{ route('admin.workflow.show', $instance) }}" class="btn btn-xs btn-primary">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">Nothing awaiting your approval.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">My Requests</h3></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Workflow</th>
                    <th>Status</th>
                    <th>Current Step</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($myRequests as $instance)
                    <tr>
                        <td>{{ $instance->workflow->name }}</td>
                        <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $instance->status)) }}</span></td>
                        <td>{{ $instance->currentStep?->name ?? '-' }}</td>
                        <td>{{ $instance->created_at?->format('Y-m-d H:i') }}</td>
                        <td><a href="{{ route('admin.workflow.show', $instance) }}" class="btn btn-xs btn-info">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">You haven't initiated any workflow requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $myRequests->links() }}</div>
</div>
@stop
