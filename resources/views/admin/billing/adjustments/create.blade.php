@extends('layouts.adminlte')

@section('page_title', 'Request Adjustment')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Request Adjustment</h3></div>
        <form action="{{ route('admin.billing.adjustments.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Invoice ID</label>
                    <input type="number" name="invoice_id" value="{{ old('invoice_id', $invoice?->id) }}" class="form-control @error('invoice_id') is-invalid @enderror" required>
                    @if($invoice)
                        <small class="text-muted">{{ $invoice->invoice_number ?? 'Draft' }} — Grand Total: {{ number_format($invoice->grand_total, 2) }}, Due: {{ number_format($invoice->due_amount, 2) }}</small>
                    @endif
                    @error('invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control @error('type') is-invalid @enderror" required>
                        <option value="discount" {{ old('type') == 'discount' ? 'selected' : '' }}>Discount</option>
                        <option value="price" {{ old('type') == 'price' ? 'selected' : '' }}>Price Correction</option>
                        <option value="credit" {{ old('type') == 'credit' ? 'selected' : '' }}>Credit</option>
                        <option value="debit" {{ old('type') == 'debit' ? 'selected' : '' }}>Debit</option>
                        <option value="write_off" {{ old('type') == 'write_off' ? 'selected' : '' }}>Write-Off</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Original Value</label>
                        <input type="number" step="0.01" name="original_value" value="{{ old('original_value', $invoice?->grand_total) }}" class="form-control @error('original_value') is-invalid @enderror" required>
                        @error('original_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">New Value</label>
                        <input type="number" step="0.01" name="new_value" value="{{ old('new_value') }}" class="form-control @error('new_value') is-invalid @enderror" required>
                        @error('new_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
                    @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.adjustments.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
@stop
