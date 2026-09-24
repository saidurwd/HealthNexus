@extends('layouts.adminlte')

@section('page_title', 'Transfers')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Bed Transfers</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Admission #</th><th>From</th><th>To</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->patient?->full_name }}</td>
                            <td>{{ $transfer->admission?->admission_number }}</td>
                            <td>{{ $transfer->fromBed?->bed_code ?? '—' }}</td>
                            <td>{{ $transfer->toBed?->bed_code ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($transfer->status) }}</span></td>
                            <td><a href="{{ route('admin.ipd.transfers.show', $transfer) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No transfers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $transfers->links() }}</div>
    </div>
@stop
