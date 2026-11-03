@extends('layouts.adminlte')

@section('page_title', __('Stock adjustments'))

@section('page_content')
    @include('admin.inventory._flash')
    @can('inventory.adjustment.request')
    <form method="POST" action="{{ route('admin.inventory.adjustments.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Ask to change a stock figure') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-4"><select name="position" class="form-select" required>@foreach($positions as $p)<option value="{{ $p->id }}">{{ $p->store->name }} · {{ $p->item->code }} {{ $p->item->name }} @if($p->batch)· {{ $p->batch->batch_no }}@endif ({{ $p->quantity_on_hand + 0 }})</option>@endforeach</select></div>
            <div class="col-md-2"><input type="number" step="0.0001" name="quantity" class="form-control" placeholder="{{ __('Change (− to remove)') }}" required></div>
            <div class="col-md-2"><select name="reason_code" class="form-select">@foreach($reasons as $r)<option value="{{ $r }}">{{ __(\Illuminate\Support\Str::headline($r)) }}</option>@endforeach</select></div>
            <div class="col-md-3"><input name="reason" class="form-control" placeholder="{{ __('What happened?') }}" required maxlength="300"></div>
            <div class="col-md-1"><button class="btn btn-danger w-100">{{ __('Send') }}</button></div>
        </div></form>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Store') }}</th><th>{{ __('Item') }}</th><th class="text-end">{{ __('Change') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $a)<tr><td>{{ $a->store->name }}</td><td>{{ $a->item->code }} {{ $a->item->name }}</td><td class="text-end {{ $a->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $a->quantity + 0 }}</td><td>{{ __(\Illuminate\Support\Str::headline($a->reason_code)) }}: {{ $a->reason }}</td><td><x-ui.status-badge :status="$a->status" /></td>
            <td>@if($a->status === 'requested')@can('inventory.adjustment.approve')<form method="POST" action="{{ route('admin.inventory.adjustments.decide', $a) }}" class="d-flex gap-1">@csrf<button name="decision" value="approve" class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button><input name="reason" class="form-control form-control-sm w-auto" placeholder="{{ __('If rejecting: why') }}"><button name="decision" value="reject" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></form>@endcan @endif</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No adjustments.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
