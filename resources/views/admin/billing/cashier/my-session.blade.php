@extends('layouts.adminlte')

@section('page_title', 'My Cashier Session')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">My Cashier Session</h3></div>
        <div class="card-body">
            @if(! $session)
                <p>You do not have an open cashier session.</p>
                @can('open', \App\Models\Billing\BillingCashierSession::class)
                    <a href="{{ route('admin.billing.cashier.open') }}" class="btn btn-primary">Open Session</a>
                @endcan
            @else
                <table class="table table-sm">
                    <tr><th>Opened At</th><td>{{ $session->opened_at?->format('Y-m-d H:i') }}</td></tr>
                    <tr><th>Opening Balance</th><td>{{ number_format($session->opening_balance, 2) }}</td></tr>
                    <tr><th>Cash Collections (so far)</th><td>{{ number_format($reconciliation['expected_collections'], 2) }}</td></tr>
                    <tr><th>Cash Refunds (so far)</th><td>{{ number_format($reconciliation['expected_refunds'], 2) }}</td></tr>
                    <tr><th>Expected Cash in Drawer</th><td><strong>{{ number_format($reconciliation['expected_closing'], 2) }}</strong></td></tr>
                </table>
                @can('close', $session)
                    <a href="{{ route('admin.billing.cashier.close', $session) }}" class="btn btn-danger">Close Session</a>
                @endcan
            @endif
        </div>
    </div>
@stop
