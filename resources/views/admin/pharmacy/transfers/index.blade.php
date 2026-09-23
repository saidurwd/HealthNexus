@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Transfers')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Stock Transfers</h3>
            <div class="card-tools">
                <a href="{{ route('admin.pharmacy.transfers.create') }}" class="btn btn-primary btn-sm">New Transfer</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Transfer #</th><th>From</th><th>To</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->transfer_number }}</td>
                            <td>{{ $transfer->sourceStore?->name }}</td>
                            <td>{{ $transfer->destinationStore?->name }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($transfer->status) }}</span></td>
                            <td><a href="{{ route('admin.pharmacy.transfers.show', $transfer) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No transfers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $transfers->links() }}</div>
    </div>
@stop
