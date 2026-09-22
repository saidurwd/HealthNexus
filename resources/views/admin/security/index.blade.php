@extends('layouts.adminlte')

@section('page_title', 'Security Events')

@section('page_content')
<form method="get" action="{{ route('admin.security.index') }}" class="card card-outline card-danger mb-4">
    <div class="card-body row">
        <div class="col-3"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search"></div>
        <div class="col-3">
            <select name="severity" class="form-select">
                <option value="all">All Severities</option>
                <option value="info" {{ request('severity')==='info'?'selected':'' }}>Info</option>
                <option value="warning" {{ request('severity')==='warning'?'selected':'' }}>Warning</option>
                <option value="critical" {{ request('severity')==='critical'?'selected':'' }}>Critical</option>
            </select>
        </div>
        <div class="col-3">
            <div class="form-check mt-2">
                <input type="checkbox" name="unresolved" value="1" {{ request('unresolved')?'checked':'' }} class="form-check-input">
                <label class="form-check-label">Unresolved only</label>
            </div>
        </div>
        <div class="col-3"><button class="btn btn-primary w-100">Filter</button></div>
    </div>
</form>

<div class="card">
    <div class="card-header"><h3 class="card-title">Security Events</h3></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Severity</th>
                    <th>Resolved</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td>{{ $event->id }}</td>
                        <td>{{ $event->user?->name ?? 'Unknown' }}</td>
                        <td>{{ $event->event }}</td>
                        <td>{{ ucfirst($event->severity) }}</td>
                        <td>{{ $event->resolved_at ? $event->resolved_at->format('Y-m-d') : 'No' }}</td>
                        <td>{{ $event->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No security events found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $events->links() }}</div>
</div>
@stop
