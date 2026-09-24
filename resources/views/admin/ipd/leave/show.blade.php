@extends('layouts.adminlte')

@section('page_title', 'Leave Detail')

@section('page_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Leave — {{ $leave->patient?->full_name }} <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $leave->status)) }}</span></h3>
        <div>
            @can('approve', $leave)
                @if($leave->status === 'requested')
                    <form method="POST" action="{{ route('admin.ipd.leave.approve', $leave) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">Approve</button>
                    </form>
                @endif
                @if($leave->status === 'approved')
                    <form method="POST" action="{{ route('admin.ipd.leave.start', $leave) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning">Start Leave</button>
                    </form>
                @endif
                @if(in_array($leave->status, ['requested', 'approved']))
                    <form method="POST" action="{{ route('admin.ipd.leave.cancel', $leave) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">Cancel</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Details</h3></div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tr><th>Admission</th><td>{{ $leave->admission?->admission_number }}</td></tr>
                <tr><th>Type</th><td>{{ ucfirst(str_replace('_', ' ', $leave->leave_type)) }}</td></tr>
                <tr><th>Bed Handling</th><td>{{ ucfirst($leave->bed_handling) }}</td></tr>
                <tr><th>Reason</th><td>{{ $leave->reason ?? '—' }}</td></tr>
                <tr><th>Expected Return</th><td>{{ $leave->expected_return_at?->format('Y-m-d H:i') }}</td></tr>
                <tr><th>Actual Return</th><td>{{ $leave->actual_return_at?->format('Y-m-d H:i') ?? '—' }}</td></tr>
            </table>
        </div>
    </div>

    @can('complete', $leave)
        @if($leave->status === 'on_leave')
            <div class="card">
                <div class="card-header"><h3 class="card-title">Mark Returned</h3></div>
                <form method="POST" action="{{ route('admin.ipd.leave.return', $leave) }}" class="card-body">
                    @csrf
                    @if($leave->bed_handling === 'release')
                        <div class="mb-3">
                            <label class="form-label">Bed ID (required — bed was released during leave)</label>
                            <input type="number" name="bed_id" class="form-control" required>
                        </div>
                    @endif
                    <button type="submit" class="btn btn-success">Mark Returned</button>
                </form>
            </div>
        @endif
    @endcan
@stop
