@extends('layouts.adminlte')

@section('page_title', 'Invoices')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Invoices</h3>
            <div class="card-tools">
                @can('create', \App\Models\Billing\BillingInvoice::class)
                    <a href="{{ route('admin.billing.invoices.create') }}" class="btn btn-primary btn-sm">New Invoice</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 d-flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search invoice number..." style="max-width:250px;">
                <select name="status" class="form-control" style="max-width: 200px;">
                    <option value="">All Statuses</option>
                    @foreach(['draft','pending','finalized','partially_paid','paid','cancelled','refunded','written_off'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Grand Total</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number ?? 'Draft' }}</td>
                            <td>{{ $invoice->patient?->full_name }}</td>
                            <td>{{ $invoice->invoice_date?->format('Y-m-d') }}</td>
                            <td>{{ number_format($invoice->grand_total, 2) }}</td>
                            <td>{{ number_format($invoice->due_amount, 2) }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_',' ',$invoice->status)) }}</span></td>
                            <td><a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="btn btn-xs btn-info">View</a></td>
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
