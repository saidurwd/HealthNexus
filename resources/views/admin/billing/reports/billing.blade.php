@extends('layouts.adminlte')

@section('page_title', 'Billing Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Invoice Register') }}</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" style="max-width:220px; display:inline-block;" onchange="this.form.submit()">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(['draft','pending','finalized','partially_paid','paid','cancelled','refunded','written_off'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Invoice #') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Date') }}</th><th>{{ __('Grand Total') }}</th><th>{{ __('Paid') }}</th><th>{{ __('Due') }}</th><th>{{ __('Status') }}</th></tr></thead>
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
                        <tr><td colspan="7" class="text-center">{{ __('No invoices found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $invoices->links() }}</div>
    </div>
@stop
