@extends('layouts.adminlte')

@section('page_title', 'Login History')

@section('page_content')
<form method="get" action="{{ route('admin.login-history.index') }}" class="card card-outline card-secondary mb-4">
    <div class="card-body row">
        <div class="col-4"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name or email"></div>
        <div class="col-4">
            <select name="status" class="form-select">
                <option value="all">All Statuses</option>
                <option value="success" {{ request('status')==='success'?'selected':'' }}>Success</option>
                <option value="failed" {{ request('status')==='failed'?'selected':'' }}>Failed</option>
            </select>
        </div>
        <div class="col-4"><button class="btn btn-primary w-100">Filter</button></div>
    </div>
</form>

<div class="card">
    <div class="card-header"><h3 class="card-title">Login History</h3></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Failure Reason</th>
                    <th>IP Address</th>
                    <th>Logged In</th>
                    <th>Logged Out</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $entry)
                    <tr>
                        <td>{{ $entry->user?->name ?? '-' }}</td>
                        <td>{{ $entry->email }}</td>
                        <td>
                            <span class="badge {{ $entry->status === 'success' ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst($entry->status) }}
                            </span>
                        </td>
                        <td>{{ $entry->failure_reason ? ucfirst(str_replace('_', ' ', $entry->failure_reason)) : '-' }}</td>
                        <td>{{ $entry->ip_address }}</td>
                        <td>{{ $entry->logged_in_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $entry->logged_out_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No login history found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $history->links() }}</div>
</div>
@stop
