@extends('layouts.adminlte')

@section('page_title', 'Transfer Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Transfers') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Patient') }}</th><th>{{ __('From Ward') }}</th><th>{{ __('To Ward') }}</th><th>{{ __('Status') }}</th><th>{{ __('Moved At') }}</th></tr></thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->patient?->full_name }}</td>
                            <td>{{ $transfer->fromBed?->room?->ward?->name ?? '—' }}</td>
                            <td>{{ $transfer->toBed?->room?->ward?->name ?? '—' }}</td>
                            <td><x-ui.status-badge :status="$transfer->status" /></td>
                            <td>{{ $transfer->moved_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('No transfers found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $transfers->links() }}</div>
    </div>
@stop
