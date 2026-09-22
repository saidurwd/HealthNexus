@extends('layouts.adminlte')

@section('page_title', 'Payments')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Payments</h3>
            <div class="card-tools">
                @can('create', \App\Models\Billing\BillingPayment::class)
                    <a href="{{ route('admin.billing.payments.create') }}" class="btn btn-primary btn-sm">Collect Payment</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr><th>Payment #</th><th>Patient</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_number }}</td>
                            <td>{{ $payment->patient?->full_name }}</td>
                            <td>{{ $payment->invoice?->invoice_number ?? '-' }}</td>
                            <td>{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->method?->name }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($payment->status) }}</span></td>
                            <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                            <td><a href="{{ route('admin.billing.payments.show', $payment) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $payments->links() }}</div>
    </div>
@stop
