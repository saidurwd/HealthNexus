@extends('layouts.adminlte')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Audit Logs</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Model</th>
                                <th>IP Address</th>
                                <th>URL</th>
                                <th>Method</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td>{{ $log->id }}</td>
                                    <td>{{ $log->user?->name ?? 'System' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $log->action === 'CREATE' ? 'success' : ($log->action === 'UPDATE' ? 'warning' : ($log->action === 'DELETE' ? 'danger' : 'info')) }}">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td>{{ $log->model_type ?? 'N/A' }}</td>
                                    <td>{{ $log->ip_address ?? 'N/A' }}</td>
                                    <td>{{ $log->url ?? 'N/A' }}</td>
                                    <td>{{ $log->method ?? 'N/A' }}</td>
                                    <td>{{ $log->created_at?->diffForHumans() ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('admin.audit.show', $log) }}" class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No audit logs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
