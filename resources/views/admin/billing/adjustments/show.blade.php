@extends('layouts.adminlte')

@section('page_title', 'Adjustment #'.$adjustment->id)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Adjustment #{{ $adjustment->id }}</h3>
            <div class="card-tools"><x-ui.status-badge :status="$adjustment->status" /></div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>{{ __('Invoice') }}</th><td><a href="{{ route('admin.billing.invoices.show', $adjustment->invoice) }}">{{ $adjustment->invoice?->invoice_number ?? 'Draft' }}</a></td></tr>
                <tr><th>{{ __('Type') }}</th><td>{{ ucfirst(str_replace('_',' ',$adjustment->type)) }}</td></tr>
                <tr><th>{{ __('Original Value') }}</th><td>{{ number_format($adjustment->original_value, 2) }}</td></tr>
                <tr><th>{{ __('New Value') }}</th><td>{{ number_format($adjustment->new_value, 2) }}</td></tr>
                <tr><th>{{ __('Difference') }}</th><td>{{ number_format($adjustment->difference, 2) }}</td></tr>
                <tr><th>{{ __('Reason') }}</th><td>{{ $adjustment->reason }}</td></tr>
                <tr><th>{{ __('Requested By') }}</th><td>{{ $adjustment->requestedBy?->name }} at {{ $adjustment->requested_at?->format('Y-m-d H:i') }}</td></tr>
                @if($adjustment->approved_by)
                    <tr><th>{{ __('Approved By') }}</th><td>{{ $adjustment->approvedBy?->name }} at {{ $adjustment->approved_at?->format('Y-m-d H:i') }}</td></tr>
                @endif
                <tr><th>{{ __('Requires Higher Approval') }}</th><td>{{ $requiresApproval ? 'Yes' : 'No' }}</td></tr>
            </table>

            @can('approve', $adjustment)
                @if($adjustment->isPending())
                    <button class="btn btn-success btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#approveForm">{{ __('Approve & Apply') }}</button>
                    <div class="collapse mt-3" id="approveForm">
                        <form action="{{ route('admin.billing.adjustments.approve', $adjustment) }}" method="post">
                            @csrf
                            <div class="mb-2"><textarea name="note" class="form-control" placeholder="Approval note (optional)"></textarea></div>
                            <button class="btn btn-success">{{ __('Confirm Approval') }}</button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    </div>
@stop
