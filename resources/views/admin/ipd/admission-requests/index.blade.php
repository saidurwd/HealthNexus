@extends('layouts.adminlte')

@section('page_title', 'Admission Requests')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Admission Requests</h3>
            <div class="card-tools">
                @can('ipd.admission.create')
                    <a href="{{ route('admin.ipd.admission-requests.create') }}" class="btn btn-primary btn-sm">New Request</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['requested','pending_approval','approved','rejected','cancelled'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Type</th><th>Priority</th><th>Requested</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($admissionRequests as $req)
                        <tr>
                            <td>{{ $req->patient?->full_name }}</td>
                            <td>{{ $req->admissionType?->name ?? '—' }}</td>
                            <td>{{ ucfirst($req->priority) }}</td>
                            <td>{{ $req->created_at->format('Y-m-d H:i') }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $req->status)) }}</span></td>
                            <td><a href="{{ route('admin.ipd.admission-requests.show', $req) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No admission requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $admissionRequests->links() }}</div>
    </div>
@stop
