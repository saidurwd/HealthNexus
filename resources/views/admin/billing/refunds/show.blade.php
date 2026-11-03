@extends('layouts.adminlte')

@section('page_title', 'Refund '.$refund->refund_number)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Refund {{ $refund->refund_number }}</h3>
            <div class="card-tools">
                <x-ui.status-badge :status="$refund->status" />
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>{{ __('Patient') }}</th><td>{{ $refund->patient?->full_name }}</td></tr>
                <tr><th>{{ __('Payment') }}</th><td><a href="{{ route('admin.billing.payments.show', $refund->payment) }}">{{ $refund->payment?->payment_number }}</a></td></tr>
                <tr><th>{{ __('Invoice') }}</th><td>{{ $refund->invoice?->invoice_number ?? '-' }}</td></tr>
                <tr><th>{{ __('Amount') }}</th><td>{{ number_format($refund->amount, 2) }}</td></tr>
                <tr><th>{{ __('Reason') }}</th><td>{{ $refund->reason }}</td></tr>
                <tr><th>{{ __('Requested By') }}</th><td>{{ $refund->requestedBy?->name }} at {{ $refund->requested_at?->format('Y-m-d H:i') }}</td></tr>
                @if($refund->approved_by)
                    <tr><th>{{ __('Approved By') }}</th><td>{{ $refund->approvedBy?->name }} at {{ $refund->approved_at?->format('Y-m-d H:i') }}</td></tr>
                @endif
                @if($refund->processed_by)
                    <tr><th>{{ __('Processed By') }}</th><td>{{ $refund->processedBy?->name }} at {{ $refund->processed_at?->format('Y-m-d H:i') }}</td></tr>
                @endif
            </table>

            @can('approve', $refund)
                @if($refund->canApprove())
                    <button class="btn btn-success btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#approveForm">{{ __('Approve Refund') }}</button>
                    <div class="collapse mt-3" id="approveForm">
                        <form action="{{ route('admin.billing.refunds.approve', $refund) }}" method="post">
                            @csrf
                            <div class="mb-2"><textarea name="note" class="form-control" placeholder="Approval note (optional)"></textarea></div>
                            <button class="btn btn-success">{{ __('Confirm Approval') }}</button>
                        </form>
                    </div>
                @endif
            @endcan

            @can('process', $refund)
                @if($refund->status === 'approved')
                    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#processForm">{{ __('Process Refund') }}</button>
                    <div class="collapse mt-3" id="processForm">
                        <form action="{{ route('admin.billing.refunds.process', $refund) }}" method="post">
                            @csrf
                            <div class="mb-2"><input type="text" name="processor_reference" class="form-control" placeholder="{{ __('Processor reference (optional)') }}"></div>
                            <button class="btn btn-primary">{{ __('Confirm Processing') }}</button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    </div>
@stop
