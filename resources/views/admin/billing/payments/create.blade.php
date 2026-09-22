@extends('layouts.adminlte')

@section('page_title', 'Collect Payment')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Collect Payment</h3></div>
        <form action="{{ route('admin.billing.payments.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Invoice ID</label>
                    <input type="number" name="invoice_id" value="{{ old('invoice_id', $invoice?->id) }}" class="form-control @error('invoice_id') is-invalid @enderror" required>
                    @if($invoice)
                        <small class="text-muted">{{ $invoice->invoice_number ?? 'Draft' }} — Due: {{ number_format($invoice->due_amount, 2) }}</small>
                    @endif
                    @error('invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method_id" class="form-control @error('payment_method_id') is-invalid @enderror" required>
                        <option value="">Select Method</option>
                        @foreach($methods as $method)
                            <option value="{{ $method->id }}" {{ old('payment_method_id') == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                        @endforeach
                    </select>
                    @error('payment_method_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $invoice?->due_amount) }}" class="form-control @error('amount') is-invalid @enderror" required>
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Transaction Reference (optional)</label>
                    <input type="text" name="transaction_reference" value="{{ old('transaction_reference') }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Date</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.payments.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Collect Payment</button>
            </div>
        </form>
    </div>
@stop
