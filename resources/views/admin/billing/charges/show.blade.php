@extends('layouts.adminlte')

@section('page_title', 'Charge Details')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Charge #{{ $charge->id }}</h3>
            <div class="card-tools">
                @can('cancel', $charge)
                    @if(! $charge->isBilled() && ! $charge->isCancelled())
                        <button class="btn btn-danger btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#cancelForm">{{ __('Cancel Charge') }}</button>
                    @endif
                @endcan
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>{{ __('Patient') }}</th><td>{{ $charge->patient?->full_name }}</td></tr>
                <tr><th>{{ __('Item') }}</th><td>{{ $charge->billingItem?->name }}</td></tr>
                <tr><th>{{ __('Quantity') }}</th><td>{{ $charge->quantity }}</td></tr>
                <tr><th>{{ __('Unit Price') }}</th><td>{{ number_format($charge->unit_price, 2) }}</td></tr>
                <tr><th>{{ __('Gross') }}</th><td>{{ number_format($charge->gross_amount, 2) }}</td></tr>
                <tr><th>{{ __('Discount') }}</th><td>{{ number_format($charge->discount_amount, 2) }}</td></tr>
                <tr><th>{{ __('Tax') }}</th><td>{{ number_format($charge->tax_amount, 2) }}</td></tr>
                <tr><th>{{ __('Net') }}</th><td>{{ number_format($charge->net_amount, 2) }}</td></tr>
                <tr><th>{{ __('Status') }}</th><td>{{ ucfirst($charge->status) }}</td></tr>
                <tr><th>{{ __('Source') }}</th><td>{{ class_basename($charge->source_type) }} #{{ $charge->source_id }}</td></tr>
                <tr><th>{{ __('Encounter') }}</th><td>{{ $charge->encounter?->encounter_no ?? '-' }}</td></tr>
                <tr><th>{{ __('Charged At') }}</th><td>{{ $charge->charged_at?->format('Y-m-d H:i') }}</td></tr>
                @if($charge->invoiceItem)
                    <tr><th>{{ __('Invoice') }}</th><td><a href="{{ route('admin.billing.invoices.show', $charge->invoiceItem->invoice) }}">{{ $charge->invoiceItem->invoice->invoice_number ?? 'Draft' }}</a></td></tr>
                @endif
            </table>

            @can('cancel', $charge)
                @if(! $charge->isBilled() && ! $charge->isCancelled())
                    <div class="collapse mt-3" id="cancelForm">
                        <form action="{{ route('admin.billing.charges.cancel', $charge) }}" method="post">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('Reason') }}</label>
                                <textarea name="reason" class="form-control" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger">{{ __('Confirm Cancel') }}</button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    </div>
@stop
