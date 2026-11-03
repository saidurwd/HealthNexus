@extends('layouts.adminlte')

@section('page_title', __('Stock on hand'))

@section('page_content')
    @include('admin.inventory._flash')
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control w-auto" placeholder="{{ __('Code or name') }}">
        <select name="store" class="form-select w-auto"><option value="">{{ __('All stores') }}</option>@foreach($stores as $s)<option value="{{ $s->public_id }}" @selected(request('store') === $s->public_id)>{{ $s->name }}</option>@endforeach</select>
        <button class="btn btn-outline-secondary">{{ __('Filter') }}</button>
    </form>
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>{{ __('Store') }}</th><th>{{ __('Item') }}</th><th>{{ __('Batch') }}</th><th>{{ __('Expires') }}</th><th class="text-end">{{ __('On hand') }}</th><th class="text-end">{{ __('Set aside') }}</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td>{{ $r->store->name }}</td><td><a href="{{ route('admin.inventory.items.show', $r->item) }}">{{ $r->item->code }}</a> {{ $r->item->name }}</td><td>{{ $r->batch?->batch_no ?? '—' }}</td>
            <td class="{{ $r->batch?->expiry_date && $r->batch->expiry_date->lt(today()) ? 'text-danger' : '' }}">{{ $r->batch?->expiry_date?->toDateString() ?? '—' }}</td><td class="text-end">{{ $r->quantity_on_hand + 0 }} {{ $r->item->base_uom }}</td><td class="text-end">{{ $r->quantity_reserved + 0 }}</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('Nothing in stock.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
