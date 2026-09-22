@extends('layouts.adminlte')

@section('page_title', 'Request Refund')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Request Refund</h3></div>
        <form action="{{ route('admin.billing.refunds.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Payment ID</label>
                    <input type="number" name="payment_id" value="{{ old('payment_id', $payment?->id) }}" class="form-control @error('payment_id') is-invalid @enderror" required>
                    @if($payment)
                        <small class="text-muted">{{ $payment->payment_number }} — Amount: {{ number_format($payment->amount, 2) }}</small>
                    @endif
                    @error('payment_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Refund Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
                    @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.refunds.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
@stop
