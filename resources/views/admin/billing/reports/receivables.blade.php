@extends('layouts.adminlte')

@section('page_title', 'Receivables Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Patient Outstanding</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Invoice #</th><th>Patient</th><th>Date</th><th>Grand Total</th><th>Paid</th><th>Due</th></tr></thead>
                <tbody>
                    @forelse($byPatient as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number ?? 'Draft' }}</td>
                            <td>{{ $invoice->patient?->full_name }}</td>
                            <td>{{ $invoice->invoice_date?->format('Y-m-d') }}</td>
                            <td>{{ number_format($invoice->grand_total, 2) }}</td>
                            <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                            <td>{{ number_format($invoice->due_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No outstanding patient balances.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Corporate Outstanding</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Corporate</th><th>Invoices</th><th>Total Due</th></tr></thead>
                <tbody>
                    @forelse($byCorporate as $row)
                        <tr>
                            <td>{{ $row->corporate?->name }}</td>
                            <td>{{ $row->invoice_count }}</td>
                            <td>{{ number_format($row->total_due, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No outstanding corporate balances.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
