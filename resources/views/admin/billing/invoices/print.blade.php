@extends('layouts.adminlte')

@section('page_title', __('Invoice').' '.($invoice->invoice_number ?? __('Draft')))

@section('page_content')
    @php
        $p = $invoice->patient;
        $money = fn ($v) => \App\Support\Dashboard\Numbers::number($v, 2);
        $status = (string) $invoice->status;
    @endphp
    <style>@media print { .invoice-sheet { border: 0 !important; padding: 0 !important; } } .invoice-sheet { background: #fff; border: 1px solid #ddd; padding: 12mm; max-width: 210mm; margin: 0 auto; } .invoice-sheet .totals td, .invoice-sheet .totals th { border: 0 !important; padding: 2px 6px !important; } .invoice-sheet .grand td, .invoice-sheet .grand th { border-top: 2px solid #000 !important; font-weight: 700; } .invoice-stamp { font-size: 20pt; font-weight: 700; letter-spacing: 3px; border: 3px solid #999; color: #999; display: inline-block; padding: 0 10px; transform: rotate(-6deg); }</style>

    <div class="d-flex flex-wrap gap-2 mb-3 d-print-none">
        <button type="button" class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print') }}</button>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.billing.invoices.show', $invoice) }}">{{ __('Back to the invoice') }}</a>
    </div>

    <div class="invoice-sheet">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="h4 mb-1">{{ __('INVOICE') }}</div>
                <div><strong>{{ $invoice->invoice_number ?? __('Draft — not yet finalised') }}</strong></div>
                <div class="small">{{ __('Date') }}: {{ \App\Support\Dashboard\Numbers::digits($invoice->invoice_date?->format('d M Y') ?? '—') }}@if($invoice->due_date) · {{ __('Due') }}: {{ \App\Support\Dashboard\Numbers::digits($invoice->due_date->format('d M Y')) }}@endif</div>
            </div>
            @if(in_array($status, ['cancelled', 'written_off', 'refunded', 'draft'], true))<div class="invoice-stamp">{{ strtoupper(__(str_replace('_', ' ', $status))) }}</div>
            @elseif($status === 'paid')<div class="invoice-stamp">{{ __('PAID') }}</div>@endif
        </div>

        <table class="table table-sm table-borderless mb-3">
            <tr><th style="width:22%">{{ __('Patient') }}</th><td>{{ $p?->full_name }}</td><th style="width:22%">{{ __('Patient number') }}</th><td>{{ $p?->enterprise_patient_no }}</td></tr>
            <tr><th>{{ __('Age / sex') }}</th><td>{{ $p?->date_of_birth ? \App\Support\Dashboard\Numbers::digits((string) $p->date_of_birth->age) : '—' }} / {{ $p?->sex ?? '—' }}</td><th>{{ __('Phone') }}</th><td>{{ $p?->phone ?? '—' }}</td></tr>
            <tr><th>{{ __('Invoice type') }}</th><td>{{ strtoupper($invoice->invoice_type) }}</td><th>{{ __('Billed to') }}</th><td>{{ $invoice->corporate?->name ?? $invoice->insurancePolicy?->policy_number ?? __('Patient') }}</td></tr>
        </table>

        <table class="table table-sm">
            <thead><tr><th>#</th><th>{{ __('Description') }}</th><th class="text-end">{{ __('Qty') }}</th><th class="text-end">{{ __('Unit price') }}</th><th class="text-end">{{ __('Discount') }}</th><th class="text-end">{{ __('Tax') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead>
            <tbody>
                @forelse($invoice->items as $i => $item)
                    <tr><td>{{ \App\Support\Dashboard\Numbers::digits((string) ($i + 1)) }}</td><td>{{ $item->description }}</td><td class="text-end">{{ \App\Support\Dashboard\Numbers::number($item->quantity, 0) }}</td><td class="text-end">{{ $money($item->unit_price) }}</td><td class="text-end">{{ $money($item->discount_amount) }}</td><td class="text-end">{{ $money($item->tax_amount) }}</td><td class="text-end">{{ $money($item->net_amount) }}</td></tr>
                @empty
                    <tr><td colspan="7" class="text-center">{{ __('No items on this invoice yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="row">
            <div class="col-7">
                @if($invoice->payments->isNotEmpty())
                    <div class="fw-semibold small mb-1">{{ __('Payments received') }}</div>
                    <table class="table table-sm small">
                        <thead><tr><th>{{ __('Payment #') }}</th><th>{{ __('Date') }}</th><th>{{ __('Method') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead>
                        <tbody>@foreach($invoice->payments as $pay)<tr><td>{{ $pay->payment_number }}</td><td>{{ \App\Support\Dashboard\Numbers::digits($pay->payment_date?->format('d M Y') ?? '') }}</td><td>{{ $pay->method?->name }}</td><td class="text-end">{{ $money($pay->amount) }}</td></tr>@endforeach</tbody>
                    </table>
                @endif
            </div>
            <div class="col-5">
                <table class="table table-sm totals mb-0">
                    <tr><th>{{ __('Subtotal') }}</th><td class="text-end">{{ $money($invoice->subtotal) }}</td></tr>
                    <tr><th>{{ __('Discount') }}</th><td class="text-end">{{ $money($invoice->discount_amount) }}</td></tr>
                    <tr><th>{{ __('Tax') }}</th><td class="text-end">{{ $money($invoice->tax_amount) }}</td></tr>
                    @if((float) $invoice->rounding_amount !== 0.0)<tr><th>{{ __('Rounding') }}</th><td class="text-end">{{ $money($invoice->rounding_amount) }}</td></tr>@endif
                    <tr class="grand"><th>{{ __('Grand total') }}</th><td class="text-end">৳ {{ $money($invoice->grand_total) }}</td></tr>
                    <tr><th>{{ __('Paid') }}</th><td class="text-end">{{ $money($invoice->paid_amount) }}</td></tr>
                    <tr><th>{{ __('Balance due') }}</th><td class="text-end"><strong>৳ {{ $money($invoice->due_amount) }}</strong></td></tr>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-5 pt-4 small">
            <div style="width:40%; border-top:1px solid #000" class="pt-1">{{ __('Patient / guardian signature') }}</div>
            <div style="width:40%; border-top:1px solid #000" class="pt-1 text-end">{{ __('Cashier') }}@if($invoice->finalizedBy): {{ $invoice->finalizedBy->name }}@endif</div>
        </div>
        <div class="small text-muted mt-3">{{ __('This is a computer-generated invoice.') }}</div>
    </div>
@stop
