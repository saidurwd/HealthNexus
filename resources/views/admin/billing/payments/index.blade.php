@extends('layouts.adminlte')

@section('page_title', 'Payments')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Payments') }}</h3>
            <div class="card-tools">
                @can('create', \App\Models\Billing\BillingPayment::class)
                    <a href="{{ route('admin.billing.payments.create') }}" class="btn btn-primary btn-sm">{{ __('Collect Payment') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr><th>{{ __('Payment #') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Invoice') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Method') }}</th><th>{{ __('Status') }}</th><th>{{ __('Date') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_number }}</td>
                            <td>{{ $payment->patient?->full_name }}</td>
                            <td>{{ $payment->invoice?->invoice_number ?? '-' }}</td>
                            <td>{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->method?->name }}</td>
                            <td><x-ui.status-badge :status="$payment->status" /></td>
                            <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                            <td><a href="{{ route('admin.billing.payments.show', $payment) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">{{ __('No payments found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $payments->links() }}</div>
    </div>
@stop
