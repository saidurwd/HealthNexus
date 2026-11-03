@extends('layouts.adminlte')

@section('page_title', __('Items'))

@section('page_content')
    @include('admin.inventory._flash')
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control w-auto" placeholder="{{ __('Code or name') }}">
        <select name="category" class="form-select w-auto"><option value="">{{ __('Any category') }}</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select>
        <button class="btn btn-outline-secondary">{{ __('Filter') }}</button>
    </form>
    @can('inventory.item.manage')
    <div class="row g-3 mb-3">
        <div class="col-lg-4"><form method="POST" action="{{ route('admin.inventory.categories.store') }}" class="card h-100">@csrf
            <div class="card-header"><h3 class="card-title">{{ __('Add a category') }}</h3></div>
            <div class="card-body d-grid gap-2"><input name="code" class="form-control" placeholder="{{ __('Code') }}" required maxlength="30"><input name="name" class="form-control" placeholder="{{ __('Name') }}" required maxlength="120">
                <select name="kind" class="form-select">@foreach($kinds as $k)<option value="{{ $k }}">{{ __(\Illuminate\Support\Str::headline($k)) }}</option>@endforeach</select><button class="btn btn-outline-primary">{{ __('Add') }}</button></div></form></div>
        <div class="col-lg-8"><form method="POST" action="{{ route('admin.inventory.items.store') }}" class="card h-100">@csrf
            <div class="card-header"><h3 class="card-title">{{ __('Add an item') }}</h3></div>
            <div class="card-body row g-2">
                <div class="col-md-3"><input name="code" class="form-control" placeholder="{{ __('Code') }}" required maxlength="40"></div>
                <div class="col-md-5"><input name="name" class="form-control" placeholder="{{ __('Name') }}" required maxlength="200"></div>
                <div class="col-md-4"><select name="category_id" class="form-select" required>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="base_uom" class="form-select" title="{{ __('Counted in') }}">@foreach($uoms as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="purchase_uom" class="form-select" title="{{ __('Bought in') }}"><option value="">{{ __('Bought in the same unit') }}</option>@foreach($uoms as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach</select></div>
                <div class="col-md-3"><input type="number" step="0.0001" name="purchase_to_base" class="form-control" placeholder="{{ __('Units in one pack') }}"></div>
                <div class="col-md-3"><input type="number" step="0.01" name="standard_cost" class="form-control" placeholder="{{ __('Cost per unit') }}"></div>
                <div class="col-md-3"><input type="number" step="0.0001" name="reorder_min" class="form-control" placeholder="{{ __('Reorder at') }}"></div>
                <div class="col-md-3"><input type="number" step="0.0001" name="reorder_max" class="form-control" placeholder="{{ __('Refill up to') }}"></div>
                <div class="col-md-3"><input name="charge_service_code" class="form-control" placeholder="{{ __('Billing code (if charged to patients)') }}" maxlength="40"></div>
                <div class="col-md-3 d-flex gap-3 align-items-center"><label class="form-check"><input class="form-check-input" type="checkbox" name="tracks_batch" value="1"> {{ __('Batches') }}</label><label class="form-check"><input class="form-check-input" type="checkbox" name="tracks_expiry" value="1"> {{ __('Expiry date') }}</label></div>
                <div class="col-12"><button class="btn btn-danger">{{ __('Add item') }}</button></div>
            </div></form></div>
    </div>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Item') }}</th><th>{{ __('Category') }}</th><th>{{ __('Unit') }}</th><th class="text-end">{{ __('Reorder at') }}</th><th class="text-end">{{ __('Cost') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse($items as $i)<tr><td><a href="{{ route('admin.inventory.items.show', $i) }}">{{ $i->code }}</a></td><td>{{ $i->name }}</td><td>{{ $i->category->name }}</td><td>{{ $i->base_uom }}</td><td class="text-end">{{ $i->reorder_min + 0 }}</td><td class="text-end">{{ $i->standard_cost + 0 }}</td><td><x-ui.status-badge :status="$i->status" /></td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No items yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $items->links() }}</div></div>
@stop
