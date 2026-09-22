@extends('layouts.adminlte')

@section('page_title', 'Receipt '.$receipt->receipt_number)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Receipt {{ $receipt->receipt_number }}</h3>
            <div class="card-tools">
                <button onclick="window.print()" class="btn btn-secondary btn-sm">Print</button>
            </div>
        </div>
        <div class="card-body">
            <p><strong>{{ $receipt->company?->name }}</strong> &mdash; {{ $receipt->branch?->name }}</p>
            <table class="table table-sm">
                <tr><th>Patient</th><td>{{ $receipt->payment?->patient?->full_name }} ({{ $receipt->payment?->patient?->enterprise_patient_no }})</td></tr>
                <tr><th>Invoice</th><td>{{ $receipt->invoice?->invoice_number ?? '-' }}</td></tr>
                <tr><th>Payment #</th><td>{{ $receipt->payment?->payment_number }}</td></tr>
                <tr><th>Amount</th><td>{{ number_format($receipt->payment?->amount, 2) }} {{ $receipt->payment?->currency }}</td></tr>
                <tr><th>Payment Method</th><td>{{ $receipt->payment?->method?->name }}</td></tr>
                <tr><th>Transaction Reference</th><td>{{ $receipt->payment?->transaction_reference ?? '-' }}</td></tr>
                <tr><th>Cashier</th><td>{{ $receipt->issuedBy?->name }}</td></tr>
                <tr><th>Issued At</th><td>{{ $receipt->issued_at?->format('Y-m-d H:i') }}</td></tr>
                <tr><th>Status</th><td>{{ ucfirst($receipt->status) }}</td></tr>
            </table>
        </div>
    </div>
@stop
