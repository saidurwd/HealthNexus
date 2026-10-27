@extends('layouts.adminlte')

@section('page_title', __('Supplier invoices'))

@section('page_content')
    @include('admin.procurement._flash')
    @can('procurement.invoice.record')
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Record an invoice from a supplier') }}</h3></div>
        @forelse($orders as $o)
            <form method="POST" action="{{ route('admin.procurement.orders.invoices', $o) }}" class="card-body border-top">@csrf
                <div class="fw-semibold mb-2">{{ $o->number }} · {{ $o->supplier->name }}</div>
                <div class="d-flex flex-wrap gap-2 mb-2"><input name="invoice_number" class="form-control form-control-sm w-auto" placeholder="{{ __('Invoice number') }}" required><input type="date" name="invoice_date" value="{{ today()->toDateString() }}" class="form-control form-control-sm w-auto" required><input type="number" step="0.01" min="0" name="tax_amount" class="form-control form-control-sm" style="width:110px" placeholder="{{ __('Tax') }}"></div>
                @foreach($o->lines as $l)<div class="row g-2 mb-1"><div class="col-md-5 small pt-1">{{ $l->item->name }} <span class="text-muted">({{ __('ordered') }} {{ $l->quantity + 0 }} @ {{ $l->unit_price + 0 }})</span></div>
                    <div class="col-md-2"><input type="number" step="0.0001" min="0" name="lines[{{ $l->id }}][quantity]" class="form-control form-control-sm" placeholder="{{ __('Billed qty') }}"></div><div class="col-md-2"><input type="number" step="0.0001" min="0" name="lines[{{ $l->id }}][unit_price]" class="form-control form-control-sm" placeholder="{{ __('Billed price') }}"></div></div>@endforeach
                <button class="btn btn-sm btn-primary mt-2">{{ __('Record and check') }}</button></form>
        @empty<div class="card-body text-muted">{{ __('No orders with goods received.') }}</div>@endforelse</div>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Invoice') }}</th><th>{{ __('Supplier') }}</th><th>{{ __('Order') }}</th><th class="text-end">{{ __('Total') }}</th><th class="text-end">{{ __('Difference') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $i)<tr class="{{ $i->status === 'mismatch' ? 'table-danger' : '' }}"><td>{{ $i->invoice_number }}<br><small class="text-muted">{{ $i->invoice_date->toDateString() }}</small></td><td>{{ $i->supplier->name }}</td><td>{{ $i->order->number }}</td><td class="text-end">{{ $i->total }}</td><td class="text-end">{{ $i->variance_amount }}</td><td><x-ui.status-badge :status="$i->status" />@if($i->finance_reference)<br><small>{{ $i->finance_reference }}</small>@endif</td>
            <td>@if($i->status === 'mismatch')@can('procurement.invoice.approve')<form method="POST" action="{{ route('admin.procurement.invoices.decide', $i) }}" class="d-flex gap-1">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Why?') }}" required><button name="action" value="approve" class="btn btn-sm btn-outline-success">{{ __('Accept') }}</button><button name="action" value="reject" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></form>@endcan
                @elseif(in_array($i->status, ['matched', 'approved']) && ! $i->finance_reference)<form method="POST" action="{{ route('admin.procurement.invoices.decide', $i) }}">@csrf<button name="action" value="finance" class="btn btn-sm btn-outline-primary">{{ __('Pass on for payment') }}</button></form>@endif</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No invoices.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
