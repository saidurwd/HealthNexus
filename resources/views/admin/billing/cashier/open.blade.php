@extends('layouts.adminlte')

@section('page_title', 'Open Cashier Session')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Open Cashier Session</h3></div>
        <form action="{{ route('admin.billing.cashier.open.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Counter (optional)</label>
                    <select name="counter_id" class="form-control">
                        <option value="">-</option>
                        @foreach($counters as $counter)
                            <option value="{{ $counter->id }}">{{ $counter->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Opening Cash Balance</label>
                    <input type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', 0) }}" class="form-control @error('opening_balance') is-invalid @enderror" required>
                    @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.cashier.my-session') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Open Session</button>
            </div>
        </form>
    </div>
@stop
