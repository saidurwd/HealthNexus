@extends('layouts.adminlte')

@section('page_title', __('Department requests and issues'))

@section('page_content')
    @include('admin.inventory._flash')
    <div class="row g-3 mb-3">
        @can('inventory.request.create')
        <div class="col-lg-6"><form method="POST" action="{{ route('admin.inventory.requests.store') }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Ask the store for supplies') }}</h3></div>
            <div class="card-body row g-2"><div class="col-md-6"><select name="department_id" class="form-select" required>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><select name="store" class="form-select" required>@foreach($stores as $s)<option value="{{ $s->public_id }}">{{ $s->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><select name="item" class="form-select" required>@foreach($items as $i)<option value="{{ $i->public_id }}">{{ $i->code }} {{ $i->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" placeholder="{{ __('Quantity') }}" required></div><div class="col-md-3"><button class="btn btn-danger w-100">{{ __('Ask') }}</button></div></div></form></div>
        @endcan
        @can('inventory.request.issue')
        <div class="col-lg-6"><form method="POST" action="{{ route('admin.inventory.issues.store') }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Hand supplies over without a request') }}</h3></div>
            <div class="card-body row g-2"><div class="col-md-6"><select name="store" class="form-select" required>@foreach($stores as $s)<option value="{{ $s->public_id }}">{{ $s->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><select name="department_id" class="form-select" required>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><select name="item" class="form-select" required>@foreach($items as $i)<option value="{{ $i->public_id }}">{{ $i->code }} {{ $i->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" placeholder="{{ __('Quantity') }}" required></div><div class="col-md-3"><button class="btn btn-outline-primary w-100">{{ __('Issue') }}</button></div></div></form></div>
        @endcan
    </div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Requests') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Request') }}</th><th>{{ __('Department') }}</th><th>{{ __('Store') }}</th><th>{{ __('Items') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ $r->department->name }}</td><td>{{ $r->store->name }}</td><td>@foreach($r->lines as $l){{ $l->item->code }} {{ $l->issued_quantity + 0 }}/{{ $l->quantity + 0 }}<br>@endforeach</td><td><x-ui.status-badge :status="$r->status" /></td>
            <td>@if(in_array($r->status, ['submitted', 'partially_issued']))@can('inventory.request.issue')<form method="POST" action="{{ route('admin.inventory.requests.decide', $r) }}" class="d-flex gap-1">@csrf<button name="action" value="issue" class="btn btn-sm btn-primary">{{ __('Issue') }}</button><input name="reason" class="form-control form-control-sm w-auto" placeholder="{{ __('If rejecting: why') }}"><button name="action" value="reject" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></form>@endcan @endif</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No requests.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Recent issues (unused items can come back)') }}</h3></div>
        <ul class="list-group list-group-flush small">@forelse($issues as $i)@foreach($i->lines as $l)<li class="list-group-item d-flex justify-content-between align-items-center gap-2"><span>{{ $i->number }} · {{ $i->department->name }} · {{ $l->item->code }} {{ $l->item->name }} · {{ $l->quantity + 0 }} @if($l->returned_quantity > 0)({{ __('returned') }} {{ $l->returned_quantity + 0 }})@endif</span>
            @can('inventory.request.issue')@if($l->quantity > $l->returned_quantity)<form method="POST" action="{{ route('admin.inventory.issues.return', $l) }}" class="d-flex gap-1">@csrf<input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control form-control-sm" style="width:90px" required><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why') }}" required><button class="btn btn-sm btn-outline-secondary">{{ __('Return') }}</button></form>@endif @endcan</li>@endforeach
        @empty<li class="list-group-item text-muted">{{ __('No issues yet.') }}</li>@endforelse</ul></div>
@stop
