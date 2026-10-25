@extends('layouts.adminlte')

@section('page_title', 'Adjustments')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Adjustments') }}</h3>
            <div class="card-tools">
                @can('request', \App\Models\Billing\BillingAdjustment::class)
                    <a href="{{ route('admin.billing.adjustments.create') }}" class="btn btn-primary btn-sm">{{ __('Request Adjustment') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr><th>{{ __('Invoice') }}</th><th>{{ __('Type') }}</th><th>{{ __('Original') }}</th><th>{{ __('New') }}</th><th>{{ __('Difference') }}</th><th>{{ __('Status') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adjustment)
                        <tr>
                            <td>{{ $adjustment->invoice?->invoice_number ?? '-' }}</td>
                            <td>{{ ucfirst(str_replace('_',' ',$adjustment->type)) }}</td>
                            <td>{{ number_format($adjustment->original_value, 2) }}</td>
                            <td>{{ number_format($adjustment->new_value, 2) }}</td>
                            <td>{{ number_format($adjustment->difference, 2) }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($adjustment->status) }}</span></td>
                            <td><a href="{{ route('admin.billing.adjustments.show', $adjustment) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">{{ __('No adjustments found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $adjustments->links() }}</div>
    </div>
@stop
