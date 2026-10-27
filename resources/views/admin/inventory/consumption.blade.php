@extends('layouts.adminlte')

@section('page_title', __('Use of supplies'))

@section('page_content')
    @include('admin.inventory._flash')
    <form method="POST" action="{{ route('admin.inventory.consumption.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Record supplies used') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-2"><select name="store" class="form-select" required>@foreach($stores as $s)<option value="{{ $s->public_id }}">{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="item" class="form-select" required>@foreach($items as $i)<option value="{{ $i->public_id }}">{{ $i->code }} {{ $i->name }}</option>@endforeach</select></div>
            <div class="col-md-1"><input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" placeholder="{{ __('Qty') }}" required></div>
            <div class="col-md-2"><select name="department_id" class="form-select"><option value="">{{ __('No department') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><input name="patient" class="form-control" placeholder="{{ __('Patient number (if for a patient)') }}"></div>
            <div class="col-md-1"><input type="number" name="admission_id" class="form-control" placeholder="{{ __('Admission') }}"></div>
            <div class="col-md-1"><button class="btn btn-danger w-100">{{ __('Save') }}</button></div>
        </div><div class="card-footer small text-muted">{{ __('An item with a billing code is charged to the patient automatically; the price comes from Billing, never from the store.') }}</div></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Item') }}</th><th class="text-end">{{ __('Quantity') }}</th><th>{{ __('Charge') }}</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td>{{ $r->consumed_at->format('d M Y H:i') }}</td><td>{{ $r->item->code }} {{ $r->item->name }}</td><td class="text-end">{{ $r->quantity + 0 }} {{ $r->item->base_uom }}</td><td>{{ $r->charge_key ?? '—' }}</td></tr>
        @empty<tr><td colspan="4" class="text-muted">{{ __('Nothing recorded.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
