@extends('layouts.adminlte')

@section('page_title', $item->name)

@section('page_content')
    @include('admin.inventory._flash')
    <div class="card mb-3"><div class="card-body row g-3 small">
        <div class="col-md-3"><div class="text-muted">{{ __('Code') }}</div>{{ $item->code }}</div>
        <div class="col-md-3"><div class="text-muted">{{ __('Category') }}</div>{{ $item->category->name }}</div>
        <div class="col-md-3"><div class="text-muted">{{ __('Counted in') }}</div>{{ $item->base_uom }}@if($item->purchase_uom) · {{ __('bought in') }} {{ $item->purchase_uom }} ({{ $item->purchase_to_base + 0 }}) @endif</div>
        <div class="col-md-3"><div class="text-muted">{{ __('Issue rule') }}</div>{{ $item->issue_rule === 'fefo' ? __('Oldest expiry first') : __('First in, first out') }}</div>
        <div class="col-md-3"><div class="text-muted">{{ __('Tracked by') }}</div>{{ $item->tracks_batch ? __('batch') : __('quantity only') }}{{ $item->tracks_expiry ? ' · '.__('expiry date') : '' }}</div>
        <div class="col-md-3"><div class="text-muted">{{ __('Reorder at / up to') }}</div>{{ $item->reorder_min + 0 }} / {{ $item->reorder_max + 0 }}</div>
        <div class="col-md-3"><div class="text-muted">{{ __('Standard cost') }}</div>{{ $item->standard_cost + 0 }} @if($item->last_cost)<span class="text-muted">({{ __('last paid') }} {{ $item->last_cost + 0 }})</span>@endif</div>
        <div class="col-md-3"><div class="text-muted">{{ __('Billing code') }}</div>{{ $item->charge_service_code ?? '—' }}</div>
    </div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Where it is held') }}</h3></div>
        <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Store') }}</th><th>{{ __('Batch') }}</th><th>{{ __('Expires') }}</th><th class="text-end">{{ __('On hand') }}</th><th class="text-end">{{ __('Set aside') }}</th><th>{{ __('Batch status') }}</th></tr></thead>
        <tbody>@forelse($positions as $p)<tr><td>{{ $p->store->name }}</td><td>@if($p->batch)<a href="{{ route('admin.inventory.batches.trace', $p->batch) }}">{{ $p->batch->batch_no }}</a>@else — @endif</td><td>{{ $p->batch?->expiry_date?->toDateString() ?? '—' }}</td><td class="text-end">{{ $p->quantity_on_hand + 0 }}</td><td class="text-end">{{ $p->quantity_reserved + 0 }}</td><td>@if($p->batch)<x-ui.status-badge :status="$p->batch->status" />@endif</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('None in stock.') }}</td></tr>@endforelse</tbody></table></div></div>
@stop
