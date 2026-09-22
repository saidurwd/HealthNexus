@extends('layouts.adminlte')

@section('page_title', 'Billing Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Invoice Register</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" style="max-width:220px; display:inline-block;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['draft','pending','finalized','partially_paid','paid','cancelled','refunded','written_off'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Invoice #</th><th>Patient</th><th>Date</th><th>Grand Total</th><th>Paid</th><th>Due</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number ?? 'Draft' }}</td>
                            <td>{{ $invoice->patient?->full_name }}</td>
                            <td>{{ $invoice->invoice_date?->format('Y-m-d') }}</td>
                            <td>{{ number_format($invoice->grand_total, 2) }}</td>
                            <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                            <td>{{ number_format($invoice->due_amount, 2) }}</td>
                            <td>{{ ucfirst(str_replace('_',' ',$invoice->status)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $invoices->links() }}</div>
    </div>
@stop
