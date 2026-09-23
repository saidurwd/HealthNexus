@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Stock')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Stock On Hand</h3>
            <div class="card-tools">
                @can('receive', $stores->firstWhere('id', $storeId) ?? new \App\Models\Pharmacy\PharmacyStore)
                    <a href="{{ route('admin.pharmacy.stock.receive-form') }}" class="btn btn-primary btn-sm">Receive Stock</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3">
                    <select name="store_id" class="form-control" onchange="this.form.submit()">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search medication...">
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Medication</th><th>Batch</th><th>Expiry</th><th>Qty Available</th></tr></thead>
                <tbody>
                    @forelse($stockRows as $row)
                        <tr>
                            <td>{{ $row->medication?->name }}</td>
                            <td>{{ $row->batch?->batch_number }}</td>
                            <td>
                                {{ $row->batch?->expiry_date?->format('Y-m-d') }}
                                @if($row->batch?->is_quarantined)<span class="badge bg-danger">Quarantined</span>@endif
                            </td>
                            <td>{{ $row->quantity_available }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No stock found for this store.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $stockRows->links() }}</div>
    </div>

    @can('adjust', $stores->firstWhere('id', $storeId) ?? new \App\Models\Pharmacy\PharmacyStore)
        <div class="card">
            <div class="card-header"><h3 class="card-title">Adjust Stock</h3></div>
            <form method="POST" action="{{ route('admin.pharmacy.stock.adjust') }}" class="row g-2 p-3">
                @csrf
                <input type="hidden" name="store_id" value="{{ $storeId }}">
                <div class="col-md-3">
                    <select name="batch_id" class="form-control" required>
                        <option value="">Batch...</option>
                        @foreach($stockRows as $row)
                            <option value="{{ $row->batch_id }}">{{ $row->medication?->name }} — {{ $row->batch?->batch_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="quantity_delta" class="form-control" placeholder="+/- Qty" required>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-control" required>
                        <option value="adjustment">Adjustment</option>
                        <option value="damage">Damage</option>
                        <option value="expired">Expired</option>
                        <option value="wastage">Wastage</option>
                        <option value="correction">Correction</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="reason" class="form-control" placeholder="Reason" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-warning w-100">Adjust</button>
                </div>
            </form>
        </div>
    @endcan
@stop
