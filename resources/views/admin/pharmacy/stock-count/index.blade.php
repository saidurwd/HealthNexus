@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Stock Counts')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Start a Stock Count') }}</h3></div>
        <form method="POST" action="{{ route('admin.pharmacy.stock-count.start') }}" class="row g-2 p-3">
            @csrf
            <div class="col-md-4">
                <select name="store_id" class="form-control" required>
                    <option value="">{{ __('Select Store') }}</option>
                    @foreach(\App\Models\Pharmacy\PharmacyStore::query()->forTenant(app(\App\Services\TenantContextResolver::class)->getOrganizationId(), app(\App\Services\TenantContextResolver::class)->getBranchId())->where('is_active', true)->get() as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary">{{ __('Start Count') }}</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Stock Counts') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Count #') }}</th><th>{{ __('Store') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($stockCounts as $count)
                        <tr>
                            <td>{{ $count->count_number }}</td>
                            <td>{{ $count->store?->name }}</td>
                            <td><x-ui.status-badge :status="$count->status" /></td>
                            <td><a href="{{ route('admin.pharmacy.stock-count.show', $count) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">{{ __('No stock counts found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $stockCounts->links() }}</div>
    </div>
@stop
