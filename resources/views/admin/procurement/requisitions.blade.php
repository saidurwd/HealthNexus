@extends('layouts.adminlte')

@section('page_title', __('Purchase requisitions'))

@section('page_content')
    @include('admin.procurement._flash')
    @can('procurement.requisition.create')
    <form method="POST" action="{{ route('admin.procurement.requisitions.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Ask procurement to buy') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-2"><select name="store" class="form-select" required>@foreach($stores as $s)<option value="{{ $s->public_id }}">{{ __('Deliver to') }}: {{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="item" class="form-select" required>@foreach($items as $i)<option value="{{ $i->public_id }}">{{ $i->code }} {{ $i->name }} ({{ $i->purchase_uom ?? $i->base_uom }})</option>@endforeach</select></div>
            <div class="col-md-1"><input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" placeholder="{{ __('Qty') }}" required></div>
            <div class="col-md-2"><select name="department_id" class="form-select"><option value="">{{ __('No department') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div class="col-md-1"><select name="priority" class="form-select"><option value="routine">{{ __('Routine') }}</option><option value="urgent">{{ __('Urgent') }}</option></select></div>
            <div class="col-md-2"><input type="date" name="needed_by" class="form-control" title="{{ __('Needed by') }}"></div>
            <div class="col-md-1"><button class="btn btn-danger w-100">{{ __('Send') }}</button></div>
        </div></form>
    @endcan
    @forelse($rows as $r)
        <div class="card"><div class="card-header d-flex justify-content-between"><h3 class="card-title">{{ $r->number }} · {{ $r->store->name }} @if($r->priority === 'urgent')<span class="badge text-bg-danger">{{ __('Urgent') }}</span>@endif</h3><span>{{ $r->estimated_total }} <x-ui.status-badge :status="$r->status" /></span></div>
            <div class="card-body small">@foreach($r->lines as $l){{ $l->item->code }} {{ $l->item->name }} — {{ $l->quantity + 0 }} {{ $l->item->purchase_uom ?? $l->item->base_uom }}<br>@endforeach
                @if(($chains[$r->id] ?? collect())->isNotEmpty())<div class="mt-2 text-muted">@foreach($chains[$r->id] as $a)<span class="me-3">{{ __('Level :n', ['n' => $a->level]) }}: <x-ui.status-badge :status="$a->status" /></span>@endforeach</div>@endif</div>
            @if($r->status === 'submitted')<form method="POST" action="{{ route('admin.procurement.requisitions.decide', $r) }}" class="card-footer d-flex flex-wrap gap-2">@csrf
                <button name="action" value="approve" class="btn btn-sm btn-success">{{ __('Approve') }}</button><input name="reason" class="form-control form-control-sm w-auto" placeholder="{{ __('If rejecting: why') }}"><button name="action" value="reject" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button><button name="action" value="cancel" class="btn btn-sm btn-outline-secondary ms-auto">{{ __('Withdraw') }}</button></form>@endif
        </div>
    @empty<div class="alert alert-info">{{ __('No requisitions.') }}</div>@endforelse
    {{ $rows->links() }}
@stop
