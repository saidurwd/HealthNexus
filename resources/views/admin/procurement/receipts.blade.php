@extends('layouts.adminlte')

@section('page_title', __('Goods received'))

@section('page_content')
    @include('admin.procurement._flash')
    @can('procurement.grn.receive')
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('A delivery has arrived') }}</h3></div>
        @forelse($due as $o)
            <form method="POST" action="{{ route('admin.procurement.orders.receive', $o) }}" class="card-body border-top">@csrf
                <div class="fw-semibold mb-2"><a href="{{ route('admin.procurement.orders.show', $o) }}">{{ $o->number }}</a> · {{ $o->supplier->name }} <small class="text-muted">{{ __('due') }} {{ $o->expected_on?->toDateString() ?? '—' }}</small></div>
                @foreach($o->lines->where('closed', false) as $l)<div class="row g-2 mb-1"><div class="col-md-4 small pt-1">{{ $l->item->code }} {{ $l->item->name }} <span class="text-muted">({{ __('still due') }} {{ ($l->quantity - $l->received_quantity) + 0 }})</span></div>
                    <div class="col-md-2"><input type="number" step="0.0001" min="0" name="lines[{{ $l->id }}][quantity]" class="form-control form-control-sm" placeholder="{{ __('Arrived') }}"></div>
                    <div class="col-md-3"><input name="lines[{{ $l->id }}][batch_no]" class="form-control form-control-sm" placeholder="{{ __('Batch number') }}" @required($l->item->tracks_batch)></div>
                    <div class="col-md-3"><input type="date" name="lines[{{ $l->id }}][expiry_date]" class="form-control form-control-sm" title="{{ __('Expiry date') }}"></div></div>@endforeach
                <div class="d-flex gap-2 mt-2"><input name="delivery_note" class="form-control form-control-sm w-auto" placeholder="{{ __('Delivery note number') }}"><button class="btn btn-sm btn-primary">{{ __('Count in') }}</button></div></form>
        @empty<div class="card-body text-muted">{{ __('No orders are waiting for goods.') }}</div>@endforelse</div>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>{{ __('Receipt') }}</th><th>{{ __('Order') }}</th><th>{{ __('Supplier') }}</th><th>{{ __('Date') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td><a href="{{ route('admin.procurement.receipts.show', $r) }}">{{ $r->number }}</a></td><td>{{ $r->order->number }}</td><td>{{ $r->supplier->name }}</td><td>{{ $r->received_on->toDateString() }}</td><td><x-ui.status-badge :status="$r->status" /></td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('No deliveries yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
