@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Transfers')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Stock Transfers') }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.pharmacy.transfers.create') }}" class="btn btn-primary btn-sm">{{ __('New Transfer') }}</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Transfer #') }}</th><th>{{ __('From') }}</th><th>{{ __('To') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->transfer_number }}</td>
                            <td>{{ $transfer->sourceStore?->name }}</td>
                            <td>{{ $transfer->destinationStore?->name }}</td>
                            <td><x-ui.status-badge :status="$transfer->status" /></td>
                            <td><a href="{{ route('admin.pharmacy.transfers.show', $transfer) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
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
