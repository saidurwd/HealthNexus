@extends('layouts.adminlte')

@section('page_title', 'Patient Leave')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Patient Leave</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['requested','approved','on_leave','returned','cancelled'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Admission #</th><th>Type</th><th>Expected Return</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($leaves as $leave)
                        <tr>
                            <td>{{ $leave->patient?->full_name }}</td>
                            <td>{{ $leave->admission?->admission_number }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $leave->leave_type)) }}</td>
                            <td>{{ $leave->expected_return_at?->format('Y-m-d H:i') }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $leave->status)) }}</span></td>
                            <td><a href="{{ route('admin.ipd.leave.show', $leave) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No leave records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $leaves->links() }}</div>
    </div>
@stop
