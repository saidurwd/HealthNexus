@extends('layouts.adminlte')

@section('page_title', 'Discount Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Discounts</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 d-flex gap-2">
                <input type="date" name="from" value="{{ $from }}" class="form-control" style="max-width:180px;">
                <input type="date" name="to" value="{{ $to }}" class="form-control" style="max-width:180px;">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Invoice #</th><th>Patient</th><th>Date</th><th>Discount Type</th><th>Discount Amount</th></tr></thead>
                <tbody>
                    @forelse($discounts as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number ?? 'Draft' }}</td>
                            <td>{{ $invoice->patient?->full_name }}</td>
                            <td>{{ $invoice->invoice_date?->format('Y-m-d') }}</td>
                            <td>{{ ucfirst($invoice->discount_type) }}</td>
                            <td>{{ number_format($invoice->discount_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No discounts in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
