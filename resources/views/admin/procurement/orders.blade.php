@extends('layouts.adminlte')

@section('page_title', __('Purchase orders'))

@section('page_content')
    @include('admin.procurement._flash')
    @if($overdue > 0)<div class="alert alert-danger">{{ __(':n emergency order(s) have not been approved in time.', ['n' => $overdue]) }}</div>@endif
    <form method="GET" class="d-flex gap-2 mb-3"><select name="status" class="form-select w-auto" onchange="this.form.submit()"><option value="">{{ __('Any status') }}</option>@foreach($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select></form>
    @can('procurement.po.create')
    <form method="POST" action="{{ route('admin.procurement.orders.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('New purchase order') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-3"><select name="supplier" class="form-select" required>@foreach($suppliers as $s)<option value="{{ $s->public_id }}">{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select name="store" class="form-select" required>@foreach($stores as $s)<option value="{{ $s->public_id }}">{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="item" class="form-select" required>@foreach($items as $i)<option value="{{ $i->public_id }}">{{ $i->code }} {{ $i->name }}</option>@endforeach</select></div>
            <div class="col-md-1"><input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" placeholder="{{ __('Qty') }}" required></div>
            <div class="col-md-1"><input type="number" step="0.0001" min="0.0001" name="unit_price" class="form-control" placeholder="{{ __('Price') }}" title="{{ __('Leave empty to use the agreed or last price') }}"></div>
            <div class="col-md-2"><select name="department_id" class="form-select"><option value="">{{ __('No department') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            @can('procurement.emergency.create')<div class="col-md-8"><input name="emergency_reason" class="form-control" placeholder="{{ __('Only for an emergency: why can this not wait? It goes out now and is approved afterwards.') }}" maxlength="300"></div>@endcan
            <div class="col-md-2"><button class="btn btn-danger w-100">{{ __('Create') }}</button></div>
        </div></form>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>{{ __('Order') }}</th><th>{{ __('Supplier') }}</th><th>{{ __('Deliver to') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse($rows as $o)<tr><td><a href="{{ route('admin.procurement.orders.show', $o) }}">{{ $o->number }}</a> @if($o->po_type === 'emergency')<span class="badge text-bg-danger">{{ __('Emergency') }}</span>@endif @if($o->version > 1)<small class="text-muted">v{{ $o->version }}</small>@endif</td><td>{{ $o->supplier->name }}</td><td>{{ $o->store->name }}</td><td>{{ $o->order_date->toDateString() }}</td><td class="text-end">{{ $o->total }}</td><td><x-ui.status-badge :status="$o->status" /></td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No orders.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
