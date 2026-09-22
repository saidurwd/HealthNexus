@extends('layouts.adminlte')

@section('page_title', 'Activity Logs')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Activity Logs</h3>
        <div class="card-tools">
            <form method="get" action="{{ route('admin.activity.index') }}" class="input-group input-group-sm" style="width: 250px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control">
                <button class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Request ID</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->entity_type ? class_basename($log->entity_type) : '-' }}</td>
                        <td>{{ Str::limit($log->request_id, 20) }}</td>
                        <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No activity logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $logs->links() }}</div>
</div>
@stop
