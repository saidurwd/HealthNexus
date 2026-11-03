@extends('layouts.adminlte')

@section('page_title', __('Quotation :n', ['n' => $rfq->number]))

@section('page_content')
    @include('admin.procurement._flash')
    <div class="card mb-3"><div class="card-header d-flex justify-content-between"><h3 class="card-title">{{ __('Compare quotes') }}</h3><x-ui.status-badge :status="$rfq->status" /></div>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <thead><tr><th>{{ __('Item') }}</th><th class="text-end">{{ __('Quantity') }}</th>@foreach($comparison as $c)<th class="text-end">{{ $c['supplier'] }}@if($c['is_lowest']) <span class="badge text-bg-success">{{ __('Lowest') }}</span>@endif<br><small class="text-muted">{{ $c['delivery_days'] ? $c['delivery_days'].' '.__('days') : '' }}</small></th>@endforeach</tr></thead>
            <tbody>@foreach($rfq->lines as $l)<tr><td>{{ $l->item->code }} {{ $l->item->name }}</td><td class="text-end">{{ $l->quantity + 0 }}</td>@foreach($comparison as $c)<td class="text-end">{{ isset($c['prices'][$l->id]) ? $c['prices'][$l->id] + 0 : '—' }}</td>@endforeach</tr>@endforeach
                <tr class="fw-semibold"><td colspan="2">{{ __('Total') }}</td>@foreach($comparison as $c)<td class="text-end">{{ $c['total'] }}</td>@endforeach</tr></tbody></table></div>
        @if($rfq->status === 'sent')@can('procurement.rfq.manage')<div class="card-footer"><form method="POST" action="{{ route('admin.procurement.rfqs.award', $rfq) }}" class="d-flex flex-wrap gap-2">@csrf
            <select name="supplier" class="form-select w-auto" required>@foreach($comparison as $c)<option value="{{ \App\Models\Inventory\ProcurementSupplier::find($c['supplier_id'])->public_id }}">{{ $c['supplier'] }} · {{ $c['total'] }}</option>@endforeach</select>
            <select name="store" class="form-select w-auto" required>@foreach($stores as $s)<option value="{{ $s->public_id }}">{{ __('Deliver to') }}: {{ $s->name }}</option>@endforeach</select><button class="btn btn-success">{{ __('Award and draft the order') }}</button></form></div>@endcan @endif</div>
    @if($rfq->status === 'sent')@can('procurement.rfq.manage')
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Enter a supplier\'s prices') }}</h3></div>
        <form method="POST" action="{{ route('admin.procurement.rfqs.quote', $rfq) }}" class="card-body row g-2">@csrf
            <div class="col-md-4"><select name="supplier" class="form-select" required>@foreach($rfq->invitations as $i)<option value="{{ $i->supplier->public_id }}">{{ $i->supplier->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><input type="number" name="delivery_days" class="form-control" placeholder="{{ __('Days to deliver') }}" min="0"></div><div class="col-md-3"><input type="date" name="valid_until" class="form-control" title="{{ __('Valid until') }}"></div>
            @foreach($rfq->lines as $l)<div class="col-md-4"><label class="form-label small">{{ $l->item->code }} {{ $l->item->name }} ({{ $l->quantity + 0 }})</label><input type="number" step="0.0001" min="0.0001" name="prices[{{ $l->id }}]" class="form-control" required></div>@endforeach
            <div class="col-12"><button class="btn btn-outline-primary">{{ __('Save quotation') }}</button></div></form></div>
    @endcan @endif
@stop
