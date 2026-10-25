@extends('layouts.adminlte')

@section('page_title', 'Surgery Requests')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Surgery Requests</h3>
            @can('ot.surgery.create')
                <a href="{{ route('admin.ot.surgery-requests.create') }}" class="btn btn-primary float-right">New Request</a>
            @endcan
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['requested','pending_approval','approved','rejected','cancelled'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$status)) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Procedure</th><th>Priority</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td>{{ $req->patient?->full_name }}</td>
                            <td>{{ $req->procedure?->name ?? '—' }}</td>
                            <td>{{ ucfirst($req->priority) }}</td>
                            <td><span class="badge bg-info">{{ ucwords(str_replace('_',' ',$req->status)) }}</span></td>
                            <td><a href="{{ route('admin.ot.surgery-requests.show', $req) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No surgery requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $requests->links() }}</div>
    </div>
@stop
