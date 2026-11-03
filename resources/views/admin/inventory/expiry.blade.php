@extends('layouts.adminlte')

@section('page_title', __('Expiry, holds and recalls'))

@section('page_content')
    @include('admin.inventory._flash')
    <form method="GET" class="d-flex gap-2 mb-3"><select name="days" class="form-select w-auto" onchange="this.form.submit()">@foreach([30, 90, 180, 365] as $d)<option value="{{ $d }}" @selected($days === $d)>{{ __('Within :d days', ['d' => $d]) }}</option>@endforeach</select></form>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Stock that is about to expire, or has') }}</h3></div>
        <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Item') }}</th><th>{{ __('Store') }}</th><th>{{ __('Batch') }}</th><th>{{ __('Expires') }}</th><th class="text-end">{{ __('Days left') }}</th><th class="text-end">{{ __('Quantity') }}</th><th class="text-end">{{ __('Value at risk') }}</th></tr></thead>
        <tbody>@forelse($risk as $r)<tr class="{{ $r->days_left < 0 ? 'table-danger' : ($r->days_left <= 30 ? 'table-warning' : '') }}"><td>{{ $r->item_code }} {{ $r->item_name }}</td><td>{{ $r->store_name }}</td><td><a href="{{ route('admin.inventory.batches.trace', $r->batch_id) }}">{{ $r->batch_no }}</a></td><td>{{ \Illuminate\Support\Str::before($r->expiry_date, ' ') }}</td><td class="text-end">{{ $r->days_left }}</td><td class="text-end">{{ $r->quantity_on_hand + 0 }} {{ $r->base_uom }}</td><td class="text-end">{{ number_format((float) $r->value_at_risk, 2) }}</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('Nothing expires in this period.') }}</td></tr>@endforelse</tbody></table></div></div>
    <div class="row g-3">
        <div class="col-lg-7"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Batches on hold or recalled') }}</h3></div>
            <ul class="list-group list-group-flush small">@forelse($held as $b)<li class="list-group-item d-flex justify-content-between align-items-center gap-2"><span>{{ $b->item->code }} {{ $b->item->name }} · <a href="{{ route('admin.inventory.batches.trace', $b) }}">{{ $b->batch_no }}</a> <x-ui.status-badge :status="$b->status" /><br><span class="text-muted">{{ $b->status_reason }}</span></span>
                @if($b->status === 'quarantined')@can('inventory.expiry.manage')<form method="POST" action="{{ route('admin.inventory.batches.release', $b) }}" class="d-flex gap-1">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Why it is safe') }}" required><button class="btn btn-sm btn-outline-success">{{ __('Release') }}</button></form>@endcan @endif</li>
            @empty<li class="list-group-item text-muted">{{ __('Nothing is on hold.') }}</li>@endforelse</ul></div></div>
        <div class="col-lg-5">
            @can('inventory.expiry.manage')<form method="POST" action="{{ route('admin.inventory.recalls.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">{{ __('Recall a batch') }}</h3></div>
                <div class="card-body d-grid gap-2"><select name="item" class="form-select" required>@foreach($items as $i)<option value="{{ $i->public_id }}">{{ $i->code }} {{ $i->name }}</option>@endforeach</select>
                    <input name="batch_no" class="form-control" placeholder="{{ __('Batch number (empty = every batch)') }}"><input name="reason" class="form-control" placeholder="{{ __('Reason') }}" required maxlength="300"><button class="btn btn-danger">{{ __('Recall and quarantine') }}</button></div></form>@endcan
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Recent recalls') }}</h3></div><ul class="list-group list-group-flush small">@forelse($recalls as $r)<li class="list-group-item">{{ $r->item->code }} · {{ $r->batch_no ?: __('all batches') }} · {{ $r->quantity_blocked + 0 }} {{ __('blocked') }}<br><span class="text-muted">{{ $r->reason }}</span></li>@empty<li class="list-group-item text-muted">{{ __('None.') }}</li>@endforelse</ul></div>
        </div>
    </div>
@stop
