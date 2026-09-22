@extends('layouts.adminlte')

@section('page_title', 'Adjustment #'.$adjustment->id)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Adjustment #{{ $adjustment->id }}</h3>
            <div class="card-tools"><span class="badge bg-info">{{ ucfirst($adjustment->status) }}</span></div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>Invoice</th><td><a href="{{ route('admin.billing.invoices.show', $adjustment->invoice) }}">{{ $adjustment->invoice?->invoice_number ?? 'Draft' }}</a></td></tr>
                <tr><th>Type</th><td>{{ ucfirst(str_replace('_',' ',$adjustment->type)) }}</td></tr>
                <tr><th>Original Value</th><td>{{ number_format($adjustment->original_value, 2) }}</td></tr>
                <tr><th>New Value</th><td>{{ number_format($adjustment->new_value, 2) }}</td></tr>
                <tr><th>Difference</th><td>{{ number_format($adjustment->difference, 2) }}</td></tr>
                <tr><th>Reason</th><td>{{ $adjustment->reason }}</td></tr>
                <tr><th>Requested By</th><td>{{ $adjustment->requestedBy?->name }} at {{ $adjustment->requested_at?->format('Y-m-d H:i') }}</td></tr>
                @if($adjustment->approved_by)
                    <tr><th>Approved By</th><td>{{ $adjustment->approvedBy?->name }} at {{ $adjustment->approved_at?->format('Y-m-d H:i') }}</td></tr>
                @endif
                <tr><th>Requires Higher Approval</th><td>{{ $requiresApproval ? 'Yes' : 'No' }}</td></tr>
            </table>

            @can('approve', $adjustment)
                @if($adjustment->isPending())
                    <button class="btn btn-success btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#approveForm">Approve & Apply</button>
                    <div class="collapse mt-3" id="approveForm">
                        <form action="{{ route('admin.billing.adjustments.approve', $adjustment) }}" method="post">
                            @csrf
                            <div class="mb-2"><textarea name="note" class="form-control" placeholder="Approval note (optional)"></textarea></div>
                            <button class="btn btn-success">Confirm Approval</button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    </div>
@stop
