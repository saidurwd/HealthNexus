@extends('layouts.adminlte')

@section('page_title', 'Transfer Detail')

@section('page_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Transfer — {{ $transfer->patient?->full_name }} <span class="badge bg-info">{{ ucfirst($transfer->status) }}</span></h3>
        <div>
            @can('approve', $transfer)
                @if($transfer->status === 'requested')
                    <form method="POST" action="{{ route('admin.ipd.transfers.approve', $transfer) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">Approve</button>
                    </form>
                @endif
            @endcan
            @can('complete', $transfer)
                @if(in_array($transfer->status, ['requested', 'approved']))
                    <form method="POST" action="{{ route('admin.ipd.transfers.complete', $transfer) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">Complete Transfer</button>
                    </form>
                @endif
            @endcan
            @can('cancel', $transfer)
                @if(in_array($transfer->status, ['requested', 'approved']))
                    <form method="POST" action="{{ route('admin.ipd.transfers.cancel', $transfer) }}" class="d-inline">
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
                <tr><th>Admission</th><td>{{ $transfer->admission?->admission_number }}</td></tr>
                <tr><th>From Bed</th><td>{{ $transfer->fromBed?->bed_code }} ({{ $transfer->fromBed?->room?->ward?->name }})</td></tr>
                <tr><th>To Bed</th><td>{{ $transfer->toBed?->bed_code }} ({{ $transfer->toBed?->room?->ward?->name }})</td></tr>
                <tr><th>Reason</th><td>{{ $transfer->reason ?? '—' }}</td></tr>
                <tr><th>Requested By</th><td>{{ $transfer->requestedBy?->name }} at {{ $transfer->requested_at?->format('Y-m-d H:i') }}</td></tr>
                @if($transfer->approved_at)
                    <tr><th>Approved By</th><td>{{ $transfer->approvedBy?->name }} at {{ $transfer->approved_at->format('Y-m-d H:i') }}</td></tr>
                @endif
                @if($transfer->moved_at)
                    <tr><th>Completed By</th><td>{{ $transfer->completedBy?->name }} at {{ $transfer->moved_at->format('Y-m-d H:i') }}</td></tr>
                @endif
            </table>
        </div>
    </div>
@stop
