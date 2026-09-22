@extends('layouts.adminlte')

@section('page_title', 'Cashier Sessions')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Cashier Sessions</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr><th>User</th><th>Opening</th><th>Expected Closing</th><th>Actual Closing</th><th>Variance</th><th>Status</th><th>Opened At</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td>{{ $session->user?->name }}</td>
                            <td>{{ number_format($session->opening_balance, 2) }}</td>
                            <td>{{ number_format($session->expected_closing, 2) }}</td>
                            <td>{{ number_format($session->actual_closing, 2) }}</td>
                            <td class="{{ (float) $session->variance != 0 ? 'text-danger' : '' }}">{{ number_format($session->variance, 2) }}</td>
                            <td><span class="badge {{ $session->status === 'open' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($session->status) }}</span></td>
                            <td>{{ $session->opened_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($session->status === 'closed')
                                    <a href="{{ route('admin.billing.cashier.reconciliation', $session) }}" class="btn btn-xs btn-info">Reconciliation</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No cashier sessions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $sessions->links() }}</div>
    </div>
@stop
