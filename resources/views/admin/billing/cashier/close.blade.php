@extends('layouts.adminlte')

@section('page_title', 'Close Cashier Session')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Close Cashier Session</h3></div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>Opening Balance</th><td>{{ number_format($session->opening_balance, 2) }}</td></tr>
                <tr><th>Cash Collections</th><td>{{ number_format($reconciliation['expected_collections'], 2) }}</td></tr>
                <tr><th>Cash Refunds</th><td>{{ number_format($reconciliation['expected_refunds'], 2) }}</td></tr>
                <tr><th>Expected Closing Cash</th><td><strong>{{ number_format($reconciliation['expected_closing'], 2) }}</strong></td></tr>
            </table>
        </div>
        <form action="{{ route('admin.billing.cashier.close.store', $session) }}" method="post">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Actual Cash Counted</label>
                    <input type="number" step="0.01" min="0" name="actual_closing" value="{{ old('actual_closing') }}" class="form-control @error('actual_closing') is-invalid @enderror" required>
                    @error('actual_closing') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.cashier.my-session') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Close this session? This cannot be undone.')">Close Session</button>
            </div>
        </form>
    </div>
@stop
