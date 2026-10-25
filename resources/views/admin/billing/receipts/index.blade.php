@extends('layouts.adminlte')

@section('page_title', 'Receipts')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Receipts') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr><th>{{ __('Receipt #') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Invoice') }}</th><th>{{ __('Issued At') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td>{{ $receipt->receipt_number }}</td>
                            <td>{{ $receipt->payment?->patient?->full_name }}</td>
                            <td>{{ $receipt->invoice?->invoice_number ?? '-' }}</td>
                            <td>{{ $receipt->issued_at?->format('Y-m-d H:i') }}</td>
                            <td><a href="{{ route('admin.billing.receipts.show', $receipt) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('No receipts found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $receipts->links() }}</div>
    </div>
@stop
