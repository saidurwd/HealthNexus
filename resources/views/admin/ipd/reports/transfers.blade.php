@extends('layouts.adminlte')

@section('page_title', 'Transfer Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Transfers</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>From Ward</th><th>To Ward</th><th>Status</th><th>Moved At</th></tr></thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->patient?->full_name }}</td>
                            <td>{{ $transfer->fromBed?->room?->ward?->name ?? '—' }}</td>
                            <td>{{ $transfer->toBed?->room?->ward?->name ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($transfer->status) }}</span></td>
                            <td>{{ $transfer->moved_at?->format('Y-m-d H:i') ?? '—' }}</td>
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
