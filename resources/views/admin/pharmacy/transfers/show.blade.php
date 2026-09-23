@extends('layouts.adminlte')

@section('page_title', 'Transfer '.$transfer->transfer_number)

@section('page_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Transfer {{ $transfer->transfer_number }} <span class="badge bg-info">{{ ucfirst($transfer->status) }}</span></h3>
        <div>
            @can('approve', $transfer)
                @if($transfer->status === 'requested')
                    <form method="POST" action="{{ route('admin.pharmacy.transfers.approve', $transfer) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">Approve</button>
                    </form>
                @endif
            @endcan
            @can('dispatch', $transfer)
                @if($transfer->status === 'approved')
                    <form method="POST" action="{{ route('admin.pharmacy.transfers.dispatch', $transfer) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning">Dispatch</button>
                    </form>
                @endif
            @endcan
            @can('receive', $transfer)
                @if($transfer->status === 'dispatched')
                    <form method="POST" action="{{ route('admin.pharmacy.transfers.receive', $transfer) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">Receive</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Details</h3></div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tr><th>Source</th><td>{{ $transfer->sourceStore?->name }}</td></tr>
                <tr><th>Destination</th><td>{{ $transfer->destinationStore?->name }}</td></tr>
                <tr><th>Requested By</th><td>{{ $transfer->requestedBy?->name }} at {{ $transfer->requested_at?->format('Y-m-d H:i') }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Items</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Medication</th><th>Batch</th><th>Requested</th><th>Dispatched</th><th>Received</th></tr></thead>
                <tbody>
                    @foreach($transfer->items as $item)
                        <tr>
                            <td>{{ $item->medication?->name }}</td>
                            <td>{{ $item->batch?->batch_number }}</td>
                            <td>{{ $item->quantity_requested }}</td>
                            <td>{{ $item->quantity_dispatched ?? '—' }}</td>
                            <td>{{ $item->quantity_received ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
