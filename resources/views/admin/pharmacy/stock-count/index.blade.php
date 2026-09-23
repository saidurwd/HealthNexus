@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Stock Counts')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Start a Stock Count</h3></div>
        <form method="POST" action="{{ route('admin.pharmacy.stock-count.start') }}" class="row g-2 p-3">
            @csrf
            <div class="col-md-4">
                <select name="store_id" class="form-control" required>
                    <option value="">Select Store</option>
                    @foreach(\App\Models\Pharmacy\PharmacyStore::query()->forTenant(app(\App\Services\TenantContextResolver::class)->getCompanyId(), app(\App\Services\TenantContextResolver::class)->getBranchId())->where('is_active', true)->get() as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary">Start Count</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Stock Counts</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Count #</th><th>Store</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($stockCounts as $count)
                        <tr>
                            <td>{{ $count->count_number }}</td>
                            <td>{{ $count->store?->name }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($count->status) }}</span></td>
                            <td><a href="{{ route('admin.pharmacy.stock-count.show', $count) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No stock counts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $stockCounts->links() }}</div>
    </div>
@stop
