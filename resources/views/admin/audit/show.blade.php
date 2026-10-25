@extends('layouts.adminlte')

@section('page_title', 'Audit Log #' . $auditLog->id)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Audit Log #{{ $auditLog->id }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.audit.index') }}" class="btn btn-secondary btn-sm">{{ __('Back to Audit Logs') }}</a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tr><th>{{ __('ID') }}</th><td>{{ $auditLog->id }}</td></tr>
                <tr><th>{{ __('User') }}</th><td>{{ $auditLog->user?->name ?? 'System' }} ({{ $auditLog->user?->email ?? 'N/A' }})</td></tr>
                <tr><th>{{ __('Organization') }}</th><td>{{ $auditLog->organization_id ?? 'N/A' }}</td></tr>
                <tr><th>{{ __('Branch') }}</th><td>{{ $auditLog->branch_id ?? 'N/A' }}</td></tr>
                <tr><th>{{ __('Action') }}</th><td>{{ $auditLog->action }}</td></tr>
                <tr><th>{{ __('Model') }}</th><td>{{ $auditLog->model_type ?? 'N/A' }}</td></tr>
                <tr><th>{{ __('Model ID') }}</th><td>{{ $auditLog->model_id ?? 'N/A' }}</td></tr>
                <tr><th>{{ __('IP Address') }}</th><td>{{ $auditLog->ip_address ?? 'N/A' }}</td></tr>
                <tr><th>{{ __('User Agent') }}</th><td>{{ $auditLog->user_agent ?? 'N/A' }}</td></tr>
                <tr><th>{{ __('URL') }}</th><td>{{ $auditLog->url ?? 'N/A' }}</td></tr>
                <tr><th>{{ __('Method') }}</th><td>{{ $auditLog->method ?? 'N/A' }}</td></tr>
                <tr><th>{{ __('Created At') }}</th><td>{{ $auditLog->created_at }}</td></tr>
            </table>

            @if ($auditLog->old_values || $auditLog->new_values)
                <div class="row mt-4">
                    @if ($auditLog->old_values)
                        <div class="col-md-6">
                            <h5>{{ __('Old Values') }}</h5>
                            <pre class="bg-light p-3 rounded">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                    @if ($auditLog->new_values)
                        <div class="col-md-6">
                            <h5>{{ __('New Values') }}</h5>
                            <pre class="bg-light p-3 rounded">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@stop
