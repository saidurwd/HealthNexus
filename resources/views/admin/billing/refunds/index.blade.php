@extends('layouts.adminlte')

@section('page_title', 'Refunds')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Refunds') }}</h3>
            <div class="card-tools">
                @can('request', \App\Models\Billing\BillingRefund::class)
                    <a href="{{ route('admin.billing.refunds.create') }}" class="btn btn-primary btn-sm">{{ __('Request Refund') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" style="max-width: 200px; display:inline-block;" onchange="this.form.submit()">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(['requested','approved','processed'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead>
                    <tr><th>{{ __('Refund #') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Status') }}</th><th>{{ __('Requested At') }}</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($refunds as $refund)
                        <tr>
                            <td>{{ $refund->refund_number }}</td>
                            <td>{{ $refund->patient?->full_name }}</td>
                            <td>{{ number_format($refund->amount, 2) }}</td>
                            <td><x-ui.status-badge :status="$refund->status" /></td>
                            <td>{{ $refund->requested_at?->format('Y-m-d H:i') }}</td>
                            <td><a href="{{ route('admin.billing.refunds.show', $refund) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No refunds found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $refunds->links() }}</div>
    </div>
@stop
