@extends('layouts.adminlte')

@section('page_title', 'Controlled Drug Register')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Controlled Drug Register') }}</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3">
                    <select name="store_id" class="form-control" onchange="this.form.submit()">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Medication') }}</th><th>{{ __('Batch') }}</th><th>{{ __('Type') }}</th><th>{{ __('Direction') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Balance') }}</th><th>{{ __('Performed By') }}</th><th>{{ __('Witness') }}</th></tr></thead>
                <tbody>
                    @forelse($transactions as $txn)
                        <tr>
                            <td>{{ $txn->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $txn->medication?->name }}</td>
                            <td>{{ $txn->batch?->batch_number }}</td>
                            <td>{{ ucfirst($txn->type) }}</td>
                            <td><span class="badge {{ $txn->direction === 'in' ? 'bg-success' : 'bg-danger' }}">{{ strtoupper($txn->direction) }}</span></td>
                            <td>{{ $txn->quantity }}</td>
                            <td>{{ $txn->balance_after }}</td>
                            <td>{{ $txn->performedBy?->name }}</td>
                            <td>{{ $txn->witnessedBy?->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">{{ __('No controlled-drug transactions found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $transactions->links() }}</div>
    </div>
@stop
