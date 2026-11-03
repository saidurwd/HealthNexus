@extends('layouts.adminlte')

@section('page_title', 'Corporate Receivables')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Corporate Receivables') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Corporate') }}</th><th>{{ __('Invoices') }}</th><th>{{ __('Total Due') }}</th></tr></thead>
                <tbody>
                    @forelse($receivables as $row)
                        <tr>
                            <td>{{ $row->corporate?->name }}</td>
                            <td>{{ $row->invoice_count }}</td>
                            <td>{{ number_format($row->total_due, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">{{ __('No outstanding corporate receivables.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
