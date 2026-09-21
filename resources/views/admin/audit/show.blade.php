@extends('layouts.adminlte')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Audit Log #{{ $auditLog->id }}</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>ID</th><td>{{ $auditLog->id }}</td></tr>
                        <tr><th>User</th><td>{{ $auditLog->user?->name ?? 'System' }} ({{ $auditLog->user?->email ?? 'N/A' }})</td></tr>
                        <tr><th>Company</th><td>{{ $auditLog->company_id ?? 'N/A' }}</td></tr>
                        <tr><th>Branch</th><td>{{ $auditLog->branch_id ?? 'N/A' }}</td></tr>
                        <tr><th>Action</th><td>{{ $auditLog->action }}</td></tr>
                        <tr><th>Model</th><td>{{ $auditLog->model_type ?? 'N/A' }}</td></tr>
                        <tr><th>Model ID</th><td>{{ $auditLog->model_id ?? 'N/A' }}</td></tr>
                        <tr><th>IP Address</th><td>{{ $auditLog->ip_address ?? 'N/A' }}</td></tr>
                        <tr><th>User Agent</th><td>{{ $auditLog->user_agent ?? 'N/A' }}</td></tr>
                        <tr><th>URL</th><td>{{ $auditLog->url ?? 'N/A' }}</td></tr>
                        <tr><th>Method</th><td>{{ $auditLog->method ?? 'N/A' }}</td></tr>
                        <tr><th>Created At</th><td>{{ $auditLog->created_at }}</td></tr>
                    </table>

                    @if ($auditLog->old_values || $auditLog->new_values)
                        <div class="row mt-4">
                            @if ($auditLog->old_values)
                                <div class="col-md-6">
                                    <h5>Old Values</h5>
                                    <pre class="bg-light p-3 rounded">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                            @if ($auditLog->new_values)
                                <div class="col-md-6">
                                    <h5>New Values</h5>
                                    <pre class="bg-light p-3 rounded">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                        </div>
                    @endif

                    <a href="{{ route('admin.audit.index') }}" class="btn btn-secondary mt-3">Back to Audit Logs</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
