@extends('layouts.adminlte')

@section('page_title', 'Payment '.$payment->payment_number)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Payment {{ $payment->payment_number }}</h3>
            <div class="card-tools">
                <x-ui.status-badge :status="$payment->status" />
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>{{ __('Patient') }}</th><td>{{ $payment->patient?->full_name }}</td></tr>
                <tr><th>{{ __('Invoice') }}</th><td>
                    @if($payment->invoice)
                        <a href="{{ route('admin.billing.invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_number ?? 'Draft' }}</a>
                    @else - @endif
                </td></tr>
                <tr><th>{{ __('Amount') }}</th><td>{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td></tr>
                <tr><th>{{ __('Method') }}</th><td>{{ $payment->method?->name }}</td></tr>
                <tr><th>{{ __('Transaction Reference') }}</th><td>{{ $payment->transaction_reference ?? '-' }}</td></tr>
                <tr><th>{{ __('Payment Date') }}</th><td>{{ $payment->payment_date?->format('Y-m-d') }}</td></tr>
                <tr><th>{{ __('Received By') }}</th><td>{{ $payment->receivedBy?->name }}</td></tr>
                @if($payment->receipt)
                    <tr><th>{{ __('Receipt') }}</th><td><a href="{{ route('admin.billing.receipts.show', $payment->receipt) }}">{{ $payment->receipt->receipt_number }}</a></td></tr>
                @endif
            </table>

            @can('create', \App\Models\Billing\BillingRefund::class)
                @if($payment->status === 'completed')
                    <a href="{{ route('admin.billing.refunds.create', ['payment_id' => $payment->id]) }}" class="btn btn-warning btn-sm">{{ __('Request Refund') }}</a>
                @endif
            @endcan
            @can('cancel', $payment)
                @if($payment->isMutable())
                    <button class="btn btn-danger btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#cancelForm">{{ __('Cancel Payment') }}</button>
                @endif
            @endcan
            @can('cancel', $payment)
                <div class="collapse mt-3" id="cancelForm">
                    <form action="{{ route('admin.billing.payments.cancel', $payment) }}" method="post">
                        @csrf
                        <div class="mb-2"><textarea name="reason" class="form-control" placeholder="Reason" required></textarea></div>
                        <button class="btn btn-danger">{{ __('Confirm Cancel') }}</button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    @if($payment->refunds->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('Refunds') }}</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>{{ __('Refund #') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
                    <tbody>
                        @foreach($payment->refunds as $refund)
                            <tr>
                                <td>{{ $refund->refund_number }}</td>
                                <td>{{ number_format($refund->amount, 2) }}</td>
                                <td><a href="{{ route('admin.billing.refunds.show', $refund) }}">{{ ucfirst($refund->status) }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@stop
