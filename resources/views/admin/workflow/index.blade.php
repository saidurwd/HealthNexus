@extends('layouts.adminlte')

@section('page_title', 'My Approvals')

@section('page_content')
<div class="card">
    <div class="card-header"><h3 class="card-title">{{ __('Pending My Approval') }}</h3></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Workflow') }}</th>
                    <th>{{ __('Step') }}</th>
                    <th>{{ __('Subject') }}</th>
                    <th>{{ __('Initiated By') }}</th>
                    <th>{{ __('Submitted') }}</th>
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
                        <td><a href="{{ route('admin.workflow.show', $instance) }}" class="btn btn-xs btn-primary">{{ __('Review') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">{{ __('Nothing awaiting your approval.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">{{ __('My Requests') }}</h3></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Workflow') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Current Step') }}</th>
                    <th>{{ __('Created') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($myRequests as $instance)
                    <tr>
                        <td>{{ $instance->workflow->name }}</td>
                        <td><x-ui.status-badge :status="$instance->status" /></td>
                        <td>{{ $instance->currentStep?->name ?? '-' }}</td>
                        <td>{{ $instance->created_at?->format('Y-m-d H:i') }}</td>
                        <td><a href="{{ route('admin.workflow.show', $instance) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">{{ __('You haven\'t initiated any workflow requests.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $myRequests->links() }}</div>
</div>
@stop
