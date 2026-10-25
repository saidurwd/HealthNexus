@extends('layouts.adminlte')

@section('page_title', 'Invoice '.($invoice->invoice_number ?? 'Draft'))

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Invoice {{ $invoice->invoice_number ?? '(Draft — not yet finalized)' }}</h3>
            <div class="card-tools">
                <span class="badge bg-info">{{ ucfirst(str_replace('_',' ',$invoice->status)) }}</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr><th>{{ __('Patient') }}</th><td>{{ $invoice->patient?->full_name }} ({{ $invoice->patient?->enterprise_patient_no }})</td></tr>
                        <tr><th>{{ __('Invoice Type') }}</th><td>{{ strtoupper($invoice->invoice_type) }}</td></tr>
                        <tr><th>{{ __('Invoice Date') }}</th><td>{{ $invoice->invoice_date?->format('Y-m-d') }}</td></tr>
                        <tr><th>{{ __('Due Date') }}</th><td>{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr><th>{{ __('Subtotal') }}</th><td>{{ number_format($invoice->subtotal, 2) }}</td></tr>
                        <tr><th>{{ __('Discount') }}</th><td>{{ number_format($invoice->discount_amount, 2) }}</td></tr>
                        <tr><th>{{ __('Tax') }}</th><td>{{ number_format($invoice->tax_amount, 2) }}</td></tr>
                        <tr><th>{{ __('Rounding') }}</th><td>{{ number_format($invoice->rounding_amount, 2) }}</td></tr>
                        <tr><th><strong>{{ __('Grand Total') }}</strong></th><td><strong>{{ number_format($invoice->grand_total, 2) }}</strong></td></tr>
                        <tr><th>{{ __('Paid') }}</th><td>{{ number_format($invoice->paid_amount, 2) }}</td></tr>
                        <tr><th>{{ __('Due') }}</th><td>{{ number_format($invoice->due_amount, 2) }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                @can('finalize', $invoice)
                    @if($invoice->isMutable())
                        <form action="{{ route('admin.billing.invoices.finalize', $invoice) }}" method="post" class="d-inline" onsubmit="return confirm('Finalize this invoice? It cannot be edited afterward.')">
                            @csrf
                            <button class="btn btn-success btn-sm">{{ __('Finalize Invoice') }}</button>
                        </form>
                    @endif
                @endcan
                @can('cancel', $invoice)
                    @if(! in_array($invoice->status, ['cancelled','paid','written_off','refunded']))
                        <button class="btn btn-danger btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#cancelForm">{{ __('Cancel Invoice') }}</button>
                    @endif
                @endcan
                @can('writeOff', $invoice)
                    @if(in_array($invoice->status, ['finalized','partially_paid']))
                        <button class="btn btn-warning btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#writeOffForm">{{ __('Write Off') }}</button>
                    @endif
                @endcan
                @can('create', \App\Models\Billing\BillingPayment::class)
                    @if(in_array($invoice->status, ['finalized','partially_paid']))
                        <a href="{{ route('admin.billing.payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-primary btn-sm">{{ __('Collect Payment') }}</a>
                    @endif
                @endcan
                @can('request', \App\Models\Billing\BillingAdjustment::class)
                    @if($invoice->isFinalized())
                        <a href="{{ route('admin.billing.adjustments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-secondary btn-sm">{{ __('Request Adjustment') }}</a>
                    @endif
                @endcan
            </div>

            @can('cancel', $invoice)
                <div class="collapse mt-3" id="cancelForm">
                    <form action="{{ route('admin.billing.invoices.cancel', $invoice) }}" method="post">
                        @csrf
                        <div class="mb-2"><textarea name="reason" class="form-control" placeholder="Reason for cancellation" required></textarea></div>
                        <button class="btn btn-danger">{{ __('Confirm Cancel') }}</button>
                    </form>
                </div>
            @endcan
            @can('writeOff', $invoice)
                <div class="collapse mt-3" id="writeOffForm">
                    <form action="{{ route('admin.billing.invoices.write-off', $invoice) }}" method="post">
                        @csrf
                        <div class="mb-2"><textarea name="reason" class="form-control" placeholder="Reason for write-off" required></textarea></div>
                        <button class="btn btn-warning">{{ __('Confirm Write-Off') }}</button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Invoice Items') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr><th>{{ __('Description') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Unit Price') }}</th><th>{{ __('Discount') }}</th><th>{{ __('Tax') }}</th><th>{{ __('Net') }}</th></tr>
                </thead>
                <tbody>
                    @forelse($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ number_format($item->discount_amount, 2) }}</td>
                            <td>{{ number_format($item->tax_amount, 2) }}</td>
                            <td>{{ number_format($item->net_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No items on this invoice yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @can('update', $invoice)
            @if($invoice->isMutable() && $uninvoicedCharges->isNotEmpty())
                <div class="card-footer">
                    <h5>{{ __('Add Uninvoiced Charges') }}</h5>
                    <form action="{{ route('admin.billing.invoices.add-charges', $invoice) }}" method="post">
                        @csrf
                        <table class="table table-sm">
                            <thead><tr><th></th><th>{{ __('Item') }}</th><th>{{ __('Net Amount') }}</th><th>{{ __('Charged At') }}</th></tr></thead>
                            <tbody>
                                @foreach($uninvoicedCharges as $charge)
                                    <tr>
                                        <td><input type="checkbox" name="charge_ids[]" value="{{ $charge->id }}"></td>
                                        <td>{{ $charge->billingItem?->name }}</td>
                                        <td>{{ number_format($charge->net_amount, 2) }}</td>
                                        <td>{{ $charge->charged_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Add Selected Charges') }}</button>
                    </form>
                </div>
            @endif
        @endcan
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Payments') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Payment #') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Method') }}</th><th>{{ __('Status') }}</th><th>{{ __('Date') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_number }}</td>
                            <td>{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->method?->name }}</td>
                            <td>{{ ucfirst($payment->status) }}</td>
                            <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                            <td><a href="{{ route('admin.billing.payments.show', $payment) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No payments yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($invoice->refunds->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('Refunds') }}</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>{{ __('Refund #') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
                    <tbody>
                        @foreach($invoice->refunds as $refund)
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
